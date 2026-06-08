<?php

namespace App\Filament\Pages\StudentHistory;

use App\Models\Course;
use App\Models\Enrollee;
use App\Models\Graduate;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProgram;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

/**
 * HasAcademicProgress trait extracted from StudentHistory.
 */
trait HasAcademicProgress
{

    /**
     * Get academic progress for ALL programs a student is enrolled in.
     * Reads from the normalized student_programs table — no re-parsing of raw enrollee data.
     */
    public function getAllAcademicProgress(): ?array
    {
        $student = $this->getStudentRecord();
        if (!$student) return null;

        // Ensure student_programs exist (auto-populate on first access)
        $this->ensureStudentPrograms($student);

        $studentPrograms = $student->studentPrograms()
            ->with(['program', 'admissionSemester', 'programMajor'])
            ->get();

        if ($studentPrograms->isEmpty() && $student->program_id) {
            // Fallback: use the student's assigned program
            $progress = $this->getAcademicProgressForProgram($student, $student->program, []);
            if ($progress) {
                $progress['admission_semester'] = $student->admissionSemester?->label;
                $progress['residency'] = [
                    'terms_enrolled' => 0,
                    'max_semesters' => ($student->program->max_residency_years ?? 3) * 2,
                    'max_years' => $student->program->max_residency_years ?? 3,
                ];
                $progress['raw_degree_name'] = $student->program->name;
                // Use 'fallback' sentinel so the blade can show a different remove button
                $progress['student_program_id'] = 'fallback';
                return [$progress];
            }
            return null;
        }

        $allProgress = [];

        foreach ($studentPrograms as $sp) {
            $program = $sp->program;
            if (!$program) continue;

            // Get admission label
            $admissionSemester = $sp->admissionSemester?->label
                ?? $this->termCodeToLabel($sp->admissionSemester?->term_code);

            // Residency data from stored value
            $maxResidencyYears = $program->max_residency_years ?? 3;
            $maxResidencySemesters = $maxResidencyYears * 2;

            // Dynamically count residency enrollments from student_enrollments
            $residencyCount = StudentEnrollment::where('student_program_id', $sp->id)
                ->whereHas('course', fn($q) => $q->whereRaw("UPPER(course_code) LIKE '%RESID%'"))
                ->distinct('semester_id')
                ->count('semester_id');

            $residencyData = [
                'terms_enrolled' => $residencyCount,
                'max_semesters' => $maxResidencySemesters,
                'max_years' => $maxResidencyYears,
            ];

            // Get progress — filter enrollments by student_program_id
            $progress = $this->getAcademicProgressForStudentProgram($student, $program, $sp);
            if ($progress) {
                $progress['admission_semester'] = $admissionSemester;
                $progress['residency'] = $residencyData;
                $progress['raw_degree_name'] = $sp->raw_degree_name ?? $program->name;
                $progress['student_program_id'] = $sp->id;
                $progress['major_name'] = $sp->programMajor?->name;
                $allProgress[] = $progress;
            }
        }

        return empty($allProgress) ? null : $allProgress;
    }


    /**
     * Additively populate student_programs from enrollee history.
     * Detects new program+major combinations from enrollee data and creates
     * missing StudentProgram records. Supports students who switch majors
     * within the same program (e.g., MPAf in Education Management → MPAf in
     * Strategic Planning and Public Policy).
     *
     * Also backfills program_major_id on existing records and links orphaned enrollments.
     */
    private function ensureStudentPrograms(Student $student): void
    {
        $matcher = \App\Services\ProgramMatcher::instance();

        // Collect existing (program_id, program_major_id) pairs to avoid duplicates
        $existingPairs = $student->studentPrograms()->get(['program_id', 'program_major_id'])
            ->map(fn ($sp) => $sp->program_id . ':' . ($sp->program_major_id ?? 'null'))
            ->toArray();

        $enrolleeRecords = Enrollee::where('student_number', $student->student_number)
            ->whereNotNull('degree_program')
            ->where('degree_program', '!=', '')
            ->orderBy('term_id')
            ->get();

        $byProgram = $enrolleeRecords->groupBy('degree_program');
        $seenPairs = $existingPairs; // Start with existing to avoid duplicates
        $newlyCreated = [];

        foreach ($byProgram as $rawDegree => $enrollments) {
            $programId = $matcher->match($rawDegree);
            if (!$programId) continue;

            $programMajorId = $matcher->matchMajor($programId, $rawDegree);
            $pairKey = $programId . ':' . ($programMajorId ?? 'null');

            if (in_array($pairKey, $seenPairs)) continue;
            $seenPairs[] = $pairKey;

            $termIds = $enrollments->pluck('term_id')->unique()->sort()->values();

            $residencyTerms = $enrollments->filter(function ($e) {
                return str_contains(strtoupper($e->courses_enrolled ?? ''), 'RESID');
            })->pluck('term_id')->unique()->count();

            try {
                $sp = \App\Models\StudentProgram::create([
                    'student_id' => $student->id,
                    'program_id' => $programId,
                    'program_major_id' => $programMajorId,
                    'raw_degree_name' => $rawDegree,
                    'admission_semester_id' => null,
                    'admission_date' => null,
                    'status' => 'active',
                    'residency_enrolled' => $residencyTerms,
                ]);
                $newlyCreated[] = ['sp' => $sp, 'termIds' => $termIds];
            } catch (\Illuminate\Database\QueryException $e) {
                // Skip duplicates (unique constraint on student_id + program_id + program_major_id)
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    continue;
                }
                throw $e;
            }
        }

        // Link orphaned enrollments to newly created student_programs
        foreach ($newlyCreated as $item) {
            $sp = $item['sp'];
            $termIds = $item['termIds'];
            $semesterIds = \App\Models\Semester::whereIn('term_code', $termIds->toArray())
                ->pluck('id')->toArray();
            if (!empty($semesterIds)) {
                StudentEnrollment::where('student_id', $student->id)
                    ->whereIn('semester_id', $semesterIds)
                    ->whereNull('student_program_id')
                    ->update(['student_program_id' => $sp->id]);
            }
        }

        // Also backfill program_major_id on existing StudentProgram records that are missing it
        $existingPrograms = $student->studentPrograms()->whereNull('program_major_id')->get();
        foreach ($existingPrograms as $sp) {
            if ($sp->raw_degree_name) {
                $majorId = $matcher->matchMajor($sp->program_id, $sp->raw_degree_name);
                if ($majorId) {
                    $sp->update(['program_major_id' => $majorId]);
                }
            }
        }

        // Backfill student_program_id on any remaining orphaned enrollments
        $orphanedEnrollments = StudentEnrollment::where('student_id', $student->id)
            ->whereNull('student_program_id')
            ->get();
        if ($orphanedEnrollments->isNotEmpty()) {
            // Build a lookup keyed by raw_degree_name for precise matching
            $student->load('studentPrograms');
            $spByRawDegree = $student->studentPrograms->keyBy('raw_degree_name');
            $semesterLookup = \Illuminate\Support\Facades\DB::table('semesters')->pluck('term_code', 'id')->toArray();

            foreach ($orphanedEnrollments as $enrollment) {
                $termCode = $semesterLookup[$enrollment->semester_id] ?? null;
                if (!$termCode) continue;

                // Find the enrollee record for this term to get the degree_program
                $enrollee = Enrollee::where('student_number', $student->student_number)
                    ->where('term_id', $termCode)
                    ->first();
                if ($enrollee && $enrollee->degree_program && isset($spByRawDegree[$enrollee->degree_program])) {
                    $enrollment->update(['student_program_id' => $spByRawDegree[$enrollee->degree_program]->id]);
                }
            }

            // Single-program fallback: if orphans still remain and student has exactly 1 program,
            // assign all remaining orphans to that single program
            $remainingOrphans = StudentEnrollment::where('student_id', $student->id)
                ->whereNull('student_program_id')
                ->count();
            if ($remainingOrphans > 0) {
                $allSps = $student->studentPrograms;
                if ($allSps->count() === 1) {
                    StudentEnrollment::where('student_id', $student->id)
                        ->whereNull('student_program_id')
                        ->update(['student_program_id' => $allSps->first()->id]);
                }
            }
        }

        // Re-link existing enrollments that may be assigned to the wrong student_program
        // (e.g., major-switchers whose enrollments were all linked to the first SP)
        $this->relinkEnrollmentsToCorrectPrograms($student);
    }


    /**
     * Re-link existing student_enrollments to the correct StudentProgram
     * based on which raw_degree_name was active in each semester.
     *
     * This handles the case where a student switched majors within the same
     * program (e.g., MPAf Education Management → MPAf Strategic Planning).
     * Before the multi-major schema change, all enrollments were linked to
     * the single StudentProgram. Now we reassign each enrollment to the SP
     * whose raw_degree_name matches the enrollee record for that semester.
     *
     * Safe and idempotent — only modifies enrollments whose current
     * student_program_id doesn't match the correct SP for that term.
     */
    public function relinkEnrollmentsToCorrectPrograms(Student $student): void
    {
        $student->load('studentPrograms');
        $sps = $student->studentPrograms;

        // Only relevant when a student has multiple programs
        if ($sps->count() <= 1) return;

        // Build lookup: raw_degree_name → StudentProgram
        $spByRawDegree = $sps->keyBy('raw_degree_name');

        // If no raw_degree_name data, nothing to re-link
        if ($spByRawDegree->keys()->filter()->isEmpty()) return;

        // Build semester_id → term_code lookup
        $semesterLookup = DB::table('semesters')->pluck('term_code', 'id')->toArray();

        // Build term_code → raw_degree_name lookup from enrollee data
        $enrolleeTermToDegree = Enrollee::where('student_number', $student->student_number)
            ->whereNotNull('degree_program')
            ->where('degree_program', '!=', '')
            ->pluck('degree_program', 'term_id')
            ->toArray();

        // Get all imported enrollments that have a student_program_id
        // (skip manual ones — those were intentionally placed by the user)
        $enrollments = StudentEnrollment::where('student_id', $student->id)
            ->whereNotNull('student_program_id')
            ->where(function ($q) {
                $q->where('source', '!=', 'manual')->orWhereNull('source');
            })
            ->get();

        foreach ($enrollments as $enrollment) {
            $termCode = $semesterLookup[$enrollment->semester_id] ?? null;
            if (!$termCode) continue;

            $correctDegree = $enrolleeTermToDegree[$termCode] ?? null;
            if (!$correctDegree) continue;

            $correctSp = $spByRawDegree[$correctDegree] ?? null;
            if (!$correctSp) continue;

            // Only update if currently assigned to the wrong SP
            if ($enrollment->student_program_id !== $correctSp->id) {
                $enrollment->update(['student_program_id' => $correctSp->id]);
            }
        }
    }


    /**
     * Get academic progress for a program using student_program_id to filter enrollments.
     * This is the clean version that uses the normalized data.
     */
    private function getAcademicProgressForStudentProgram(Student $student, Program $program, \App\Models\StudentProgram $sp): ?array
    {
        // ── Build curriculum ──
        $curriculumQuery = ProgramCourse::with(['course', 'programMajor'])
            ->where('program_id', $program->id);

        // Use the per-program major (from student_programs), not the student's global major
        $majorId = $sp->program_major_id;
        if ($majorId) {
            $curriculumQuery->where(function ($q) use ($majorId) {
                $q->whereNull('program_major_id')
                  ->orWhere('program_major_id', $majorId);
            });
        }

        $curriculum = $curriculumQuery->get();

        // Deduplicate
        $deduped = collect();
        $seenCourseIds = [];
        foreach ($curriculum->sortBy(function ($pc) use ($majorId) {
            if ($pc->program_major_id === $majorId) return 0;
            if ($pc->program_major_id === null) return 1;
            return 2;
        }) as $mapping) {
            if (in_array($mapping->course_id, $seenCourseIds)) continue;
            $seenCourseIds[] = $mapping->course_id;
            $deduped->push($mapping);
        }
        $curriculum = $deduped;
        $curriculumCourseIds = $curriculum->pluck('course_id')->toArray();

        // Get enrollments filtered by student_program_id
        $enrollments = StudentEnrollment::with(['course', 'semester'])
            ->where('student_id', $student->id)
            ->where('student_program_id', $sp->id)
            ->get();

        // Build completed course IDs
        $completedCourseIds = $enrollments
            ->where('status', 'completed')
            ->pluck('course_id')
            ->unique()
            ->toArray();

        // ── Current term detection ──
        $currentTermId = $enrollments->max('semester_id');

        // ── Build type progress + courses taken ──
        $minUnitsPerType = $program->min_units_per_type ?? [];
        $typeProgress = [];
        $variableTypes = ['Major', 'Specialization', 'Cognate', 'Elective'];

        foreach ($minUnitsPerType as $typeLabel => $minUnits) {
            if (!isset($typeProgress[$typeLabel])) {
                $typeProgress[$typeLabel] = [
                    'label' => $typeLabel,
                    'required_units' => $minUnits,
                    'earned_units' => 0,
                    'total_courses' => 0,
                    'completed_courses' => 0,
                    'color' => \App\Filament\Pages\CurriculumMap::getTypeColor($typeLabel),
                    'is_variable' => in_array($typeLabel, $variableTypes),
                ];
            }
        }

        $curriculumByType = [];
        $coursesTakenByType = [];
        $unmatchedCourses = [];

        foreach ($curriculum as $mapping) {
            $course = $mapping->course;
            if (!$course) continue;
            if ($this->isResidencyCourse($course->course_code)) continue;

            // Determine the base type from curriculum mapping
            $baseTypeEnum = $mapping->course_type;
            $baseTypeLabel = $baseTypeEnum instanceof \App\Enums\CourseType
                ? $baseTypeEnum->label()
                : ($baseTypeEnum ? (\App\Enums\CourseType::tryFrom($baseTypeEnum)?->label() ?? 'Other') : 'Other');

            // Filter enrollments for this course
            $courseEnrollments = $enrollments->where('course_id', $course->id);

            // For UI display, pick the best representative enrollment:
            // A completed one if possible, otherwise the most recent one.
            $enrollment = $courseEnrollments->sortByDesc('semester.term_code')
                ->sortByDesc(fn ($e) => $e->status === 'completed' ? 1 : 0)
                ->first();

            // Per-student course type override: if the enrollment has an override, use it
            $typeLabel = $baseTypeLabel;
            if ($enrollment && $enrollment->course_type_override) {
                $overrideLabel = \App\Enums\CourseType::tryFrom($enrollment->course_type_override)?->label();
                if ($overrideLabel) {
                    $typeLabel = $overrideLabel;
                }
            }

            if (!isset($typeProgress[$typeLabel])) {
                $typeProgress[$typeLabel] = [
                    'label' => $typeLabel,
                    'required_units' => $minUnitsPerType[$typeLabel] ?? null,
                    'earned_units' => 0,
                    'total_courses' => 0,
                    'completed_courses' => 0,
                    'color' => \App\Filament\Pages\CurriculumMap::getTypeColor($typeLabel),
                    'is_variable' => in_array($typeLabel, $variableTypes),
                ];
            }
            $typeProgress[$typeLabel]['total_courses']++;

            $isCompleted = in_array($course->id, $completedCourseIds);
            $mappingUnits = $mapping->units ?? $this->estimateCourseUnits($course->course_code) ?: 3;

            if ($isCompleted) {
                $typeProgress[$typeLabel]['completed_courses']++;

                // Sum actual units earned from all completed instances of this course
                $earnedSum = $courseEnrollments->where('status', 'completed')->sum('units_earned');

                // If sum is 0 (e.g., old data without units_earned or manual status update),
                // fallback to the required curriculum mapped units.
                if ($earnedSum == 0) {
                    $earnedSum = $mappingUnits;
                }

                $typeProgress[$typeLabel]['earned_units'] += $earnedSum;
            }

            // Build curriculum reference data
            $majorLabel = $mapping->programMajor?->name;
            $isTaken = $courseEnrollments->isNotEmpty();
            $courseData = [
                'course_code' => $course->course_code,
                'course_name' => $course->course_name,
                'units' => $mappingUnits,
                'type' => $typeLabel,
                'color' => \App\Filament\Pages\CurriculumMap::getTypeColor($typeLabel),
                'is_completed' => $isCompleted,
                'is_taken' => $isTaken,
                'is_required' => $mapping->is_required,
                'term_taken' => $enrollment?->semester?->label ?? $enrollment?->semester?->term_code,
                'term_code_raw' => $enrollment?->semester?->term_code,
                'sub_group' => $mapping->sub_group ?? $majorLabel,
            ];

            $groupKey = $mapping->sub_group ?? $majorLabel;
            if (in_array($typeLabel, ['Major', 'Specialization']) && $groupKey) {
                $curriculumByType[$typeLabel][$groupKey][] = $courseData;
            } else {
                $curriculumByType[$typeLabel][] = $courseData;
            }

            // Build courses taken data
            if ($enrollment) {
                $isCurrentTerm = ($enrollment->semester_id === $currentTermId);
                $status = $enrollment->status;
                if ($this->isThesisLikeCourse($course->course_code) && $status === 'completed') {
                    $graduate = \App\Models\Graduate::where('student_number', $student->student_number)->first();
                    if (!$graduate) {
                        $status = 'enrolled';
                    }
                }

                $takenData = [
                    'enrollment_id' => $enrollment->id,
                    'course_code' => $course->course_code,
                    'course_name' => $course->course_name,
                    'units' => $enrollment->units_earned !== null && $enrollment->units_earned > 0 ? $enrollment->units_earned : $mappingUnits,
                    'grade' => $enrollment->grade,
                    'term_taken' => $enrollment->semester?->label ?? $enrollment->semester?->term_code,
                    'term_code_raw' => $enrollment->semester?->term_code,
                    'status' => $status,
                    'type' => $typeLabel,
                    'type_color' => \App\Filament\Pages\CurriculumMap::getTypeColor($typeLabel),
                    'course_type_override' => $enrollment->course_type_override,
                    'source' => $enrollment->source,
                ];

                if (in_array($typeLabel, ['Major', 'Specialization']) && $groupKey) {
                    $coursesTakenByType[$typeLabel][$groupKey][] = $takenData;
                } else {
                    $coursesTakenByType[$typeLabel][] = $takenData;
                }
            }
        }

        // Find unmatched enrollments
        foreach ($enrollments as $enrollment) {
            if (in_array($enrollment->course_id, $curriculumCourseIds)) continue;
            if (!$enrollment->course) continue;
            if ($this->isResidencyCourse($enrollment->course->course_code)) continue;

            $unmatchedCourses[] = [
                'enrollment_id' => $enrollment->id,
                'course_code' => $enrollment->course->course_code,
                'course_name' => $enrollment->course->course_name,
                'units' => $enrollment->units_earned ?: $this->estimateCourseUnits($enrollment->course->course_code),
                'term_taken' => $enrollment->semester?->label ?? $enrollment->semester?->term_code,
                'term_code_raw' => $enrollment->semester?->term_code,
                'status' => $enrollment->status,
                'source' => $enrollment->source,
            ];
        }

        // Total progress
        $totalRequired = $program->total_units_required;
        $totalEarned = array_sum(array_column($typeProgress, 'earned_units'));

        // GWA Calculation — only for this program's curriculum courses
        $gradeMap = [
            '1.0' => 1.0, '1.00' => 1.0, '1.25' => 1.25,
            '1.5' => 1.5, '1.50' => 1.5, '1.75' => 1.75,
            '2.0' => 2.0, '2.00' => 2.0, '2.25' => 2.25,
            '2.5' => 2.5, '2.50' => 2.5, '2.75' => 2.75,
            '3.0' => 3.0, '3.00' => 3.0,
        ];
        $gwaWeightedSum = 0;
        $gwaUnitSum = 0;
        foreach ($enrollments->whereIn('course_id', $curriculumCourseIds) as $enrollment) {
            if ($enrollment->status !== 'completed' || !$enrollment->grade) continue;
            $numericGrade = $gradeMap[$enrollment->grade] ?? $enrollment->grade_numeric ?? null;
            if ($numericGrade === null || $numericGrade > 3.0) continue;
            $units = $enrollment->units_earned ?: 3;
            $gwaWeightedSum += $numericGrade * $units;
            $gwaUnitSum += $units;
        }
        $gwa = $gwaUnitSum > 0 ? round($gwaWeightedSum / $gwaUnitSum, 4) : null;

        // Determine collapsible types
        $collapsibleTypes = [];
        $inlineTypes = [];
        foreach ($curriculumByType as $type => $courses) {
            if (in_array($type, ['Major', 'Specialization']) && is_array($courses) && !isset($courses[0])) {
                $collapsibleTypes[$type] = $courses;
            } elseif (is_array($courses) && count($courses) > 5) {
                $collapsibleTypes[$type] = $courses;
            } else {
                $inlineTypes[$type] = $courses;
            }
        }

        $unmatchedUnits = collect($unmatchedCourses)->sum(fn ($c) => (int) ($c['units'] ?? 0));

        // Sort types in desired display order
        $typeOrder = ['Prescribed', 'Core', 'Major', 'Specialization', 'Elective', 'Cognate', 'Seminar', 'Field Study', 'Thesis', 'Dissertation', 'Other'];
        $sortByTypeOrder = function ($arr) use ($typeOrder) {
            $sorted = [];
            foreach ($typeOrder as $type) {
                if (isset($arr[$type])) $sorted[$type] = $arr[$type];
            }
            foreach ($arr as $type => $val) {
                if (!isset($sorted[$type])) $sorted[$type] = $val;
            }
            return $sorted;
        };
        $coursesTakenByType = $sortByTypeOrder($coursesTakenByType);
        $curriculumByType = $sortByTypeOrder($curriculumByType);

        // Sort type_progress
        usort($typeProgress, function ($a, $b) use ($typeOrder) {
            $ia = array_search($a['label'], $typeOrder);
            $ib = array_search($b['label'], $typeOrder);
            return ($ia === false ? 99 : $ia) - ($ib === false ? 99 : $ib);
        });

        // Sync total_units_earned + gwa back to student_programs for list-view columns
        if ($sp->total_units_earned !== $totalEarned || $sp->gwa != $gwa) {
            $sp->update([
                'total_units_earned' => $totalEarned,
                'gwa' => $gwa,
            ]);
        }

        // ── Thesis/Dissertation/Field Study Enrollment Timeline ──
        // Collect ALL enrollments (not deduplicated) for thesis-like courses
        $thesisEnrollments = [];
        foreach ($enrollments as $enrollment) {
            if (!$enrollment->course) continue;
            if (!$this->isThesisLikeCourse($enrollment->course->course_code)) continue;

            $courseCode = $enrollment->course->course_code;
            if (!isset($thesisEnrollments[$courseCode])) {
                $thesisEnrollments[$courseCode] = [
                    'course_code' => $courseCode,
                    'course_name' => $enrollment->course->course_name,
                    'terms' => [],
                    'total_terms' => 0,
                    'latest_status' => null,
                ];
            }

            $termLabel = $enrollment->semester?->label ?? $enrollment->semester?->term_code ?? '?';
            $termCode = $enrollment->semester?->term_code ?? '?';

            $thesisEnrollments[$courseCode]['terms'][] = [
                'term_code' => $termCode,
                'term_label' => $termLabel,
                'status' => $enrollment->status,
                'units' => $enrollment->units_earned,
            ];
            $thesisEnrollments[$courseCode]['total_terms']++;
            $thesisEnrollments[$courseCode]['latest_status'] = $enrollment->status;
        }

        // Sort each thesis course's terms by term code
        foreach ($thesisEnrollments as &$te) {
            usort($te['terms'], fn ($a, $b) => strcmp($a['term_code'], $b['term_code']));
            // Latest status = status of the most recent term
            $te['latest_status'] = end($te['terms'])['status'] ?? null;
        }
        unset($te);

        // ── Residency Enrollment Timeline ──
        // Collect term codes for residency enrollments (not skipped from student_enrollments)
        $residencyTerms = [];
        foreach ($enrollments as $enrollment) {
            if (!$enrollment->course) continue;
            if (!$this->isResidencyCourse($enrollment->course->course_code)) continue;

            $termCode = $enrollment->semester?->term_code ?? '?';
            $termLabel = $enrollment->semester?->label ?? $termCode;

            $residencyTerms[] = [
                'term_code' => $termCode,
                'term_label' => $termLabel,
            ];
        }
        usort($residencyTerms, fn ($a, $b) => strcmp($a['term_code'], $b['term_code']));

        return [
            'student' => $student,
            'program' => $program,
            'type_progress' => array_values($typeProgress),
            'courses_taken_by_type' => $coursesTakenByType,
            'unmatched_courses' => $unmatchedCourses,
            'unmatched_units' => $unmatchedUnits,
            'curriculum_inline' => $inlineTypes,
            'curriculum_collapsible' => $collapsibleTypes,
            'total_required' => $totalRequired,
            'total_earned' => $totalEarned,
            'total_courses' => $curriculum->count(),
            'completed_courses' => count($completedCourseIds),
            'gwa' => $gwa,
            'thesis_enrollments' => array_values($thesisEnrollments),
            'residency_terms' => $residencyTerms,
        ];
    }


    /**
     * Get academic progress for a SPECIFIC program (legacy fallback).
     */
    private function getAcademicProgressForProgram(Student $student, Program $program, array $programCourseCodes): ?array
    {

        // ── Build curriculum query filtered by student's major ──
        $curriculumQuery = ProgramCourse::with(['course', 'programMajor'])
            ->where('program_id', $program->id);

        if ($student->program_major_id) {
            // Student has a major: show shared courses (null major) + their specific major
            $curriculumQuery->where(function ($q) use ($student) {
                $q->whereNull('program_major_id')
                  ->orWhere('program_major_id', $student->program_major_id);
            });
        }
        // If no major assigned, show ALL courses (but deduplicate below)

        $curriculum = $curriculumQuery->get();

        // ── Deduplicate: only keep one entry per course_id ──
        // Priority: student's major > first major found > shared (null)
        $deduped = collect();
        $seenCourseIds = [];
        foreach ($curriculum->sortBy(function ($pc) use ($student) {
            // Sort: student's major first, then shared (null), then others
            if ($pc->program_major_id === $student->program_major_id) return 0;
            if ($pc->program_major_id === null) return 1;
            return 2;
        }) as $mapping) {
            if (in_array($mapping->course_id, $seenCourseIds)) continue;
            $seenCourseIds[] = $mapping->course_id;
            $deduped->push($mapping);
        }
        $curriculum = $deduped;

        // Get all enrollments for this student, filtered to this program's terms
        $allEnrollments = StudentEnrollment::with(['course', 'semester'])
            ->where('student_id', $student->id)
            ->get();

        // Extract term_ids from this program's enrollee data
        $programTermIds = collect($programCourseCodes)->flatten()->unique()->values()->toArray();

        // If we have program-specific terms, filter enrollments to only semesters in those terms
        if (!empty($programTermIds)) {
            $programSemesterIds = DB::table('semesters')
                ->whereIn('term_code', $programTermIds)
                ->pluck('id')
                ->toArray();
            $enrollments = $allEnrollments->whereIn('semester_id', $programSemesterIds);
        } else {
            $enrollments = $allEnrollments;
        }

        // Build completed course IDs set
        $completedCourseIds = $enrollments
            ->where('status', 'completed')
            ->pluck('course_id')
            ->unique()
            ->toArray();

        $enrolledCourseIds = $enrollments
            ->pluck('course_id')
            ->unique()
            ->toArray();

        $minUnitsPerType = $program->min_units_per_type ?? [];

        // Standard display order
        $typeOrder = ['Core', 'Prescribed', 'Major', 'Specialization', 'Elective', 'Cognate', 'Seminar', 'Dissertation', 'Thesis', 'Field Study'];

        // ── Build type progress and curriculum data ──
        // Pre-populate from min_units_per_type so required types show even with no courses mapped
        $variableTypes = ['Major', 'Specialization', 'Cognate', 'Elective'];
        $typeProgress = [];
        foreach ($minUnitsPerType as $typeLabel => $minUnits) {
            $typeProgress[$typeLabel] = [
                'label' => $typeLabel,
                'required_units' => $minUnits,
                'earned_units' => 0,
                'total_courses' => 0,
                'completed_courses' => 0,
                'color' => \App\Filament\Pages\CurriculumMap::getTypeColor($typeLabel),
                'is_variable' => in_array($typeLabel, $variableTypes),
            ];
        }
        $curriculumByType = [];

        foreach ($curriculum as $mapping) {
            $course = $mapping->course;
            if (!$course) continue;

            // Skip residency course entries — residency is tracked as terms enrolled
            if ($this->isResidencyCourse($course->course_code)) continue;

            $typeEnum = $mapping->course_type;
            $typeLabel = $typeEnum instanceof \App\Enums\CourseType
                ? $typeEnum->label()
                : ($typeEnum ? (\App\Enums\CourseType::tryFrom($typeEnum)?->label() ?? 'Other') : 'Other');

            if (!isset($typeProgress[$typeLabel])) {
                // Types with many catalog options where "X of Y" is misleading
                $variableTypes = ['Major', 'Specialization', 'Cognate', 'Elective'];
                $typeProgress[$typeLabel] = [
                    'label' => $typeLabel,
                    'required_units' => $minUnitsPerType[$typeLabel] ?? null,
                    'earned_units' => 0,
                    'total_courses' => 0,
                    'completed_courses' => 0,
                    'color' => \App\Filament\Pages\CurriculumMap::getTypeColor($typeLabel),
                    'is_variable' => in_array($typeLabel, $variableTypes),
                ];
            }

            $typeProgress[$typeLabel]['total_courses']++;

            $isTaken = in_array($course->id, $enrolledCourseIds);
            $isCompleted = in_array($course->id, $completedCourseIds);

            if ($isCompleted) {
                $typeProgress[$typeLabel]['completed_courses']++;
                $typeProgress[$typeLabel]['earned_units'] += (int) ($mapping->units ?? $this->estimateCourseUnits($course->course_code) ?: 0);
            }

            // Build curriculum reference entry
            $enrollment = $enrollments->where('course_id', $course->id)
                ->sortByDesc(fn ($e) => $e->semester?->term_code)
                ->first();

            $majorLabel = $mapping->programMajor?->name;

            $entry = [
                'course_code' => $course->course_code,
                'course_name' => $course->course_name,
                'units' => $mapping->units ?? $this->estimateCourseUnits($course->course_code) ?: null,
                'is_required' => $mapping->is_required,
                'is_taken' => $isTaken,
                'is_completed' => $isCompleted,
                'term_taken' => $enrollment?->semester?->label ?? $enrollment?->semester?->term_code,
                'term_code_raw' => $enrollment?->semester?->term_code,
                'color' => \App\Filament\Pages\CurriculumMap::getTypeColor($typeLabel),
                'sub_group' => $mapping->sub_group ?? $majorLabel,
            ];

            // For Major/Specialization type with a sub_group or major, nest
            if (in_array($typeLabel, ['Major', 'Specialization']) && ($mapping->sub_group || $majorLabel)) {
                $groupKey = $mapping->sub_group ?? $majorLabel;
                $curriculumByType[$typeLabel][$groupKey][] = $entry;
            } else {
                $curriculumByType[$typeLabel][] = $entry;
            }
        }

        // Sort type progress by standard order
        uksort($typeProgress, function ($a, $b) use ($typeOrder) {
            $posA = array_search($a, $typeOrder);
            $posB = array_search($b, $typeOrder);
            if ($posA === false) $posA = 99;
            if ($posB === false) $posB = 99;
            return $posA - $posB;
        });

        // Sort curriculum reference by same order
        $sortedCurriculumByType = [];
        foreach ($typeOrder as $type) {
            if (isset($curriculumByType[$type])) {
                $sortedCurriculumByType[$type] = $curriculumByType[$type];
            }
        }
        foreach ($curriculumByType as $type => $courses) {
            if (!isset($sortedCurriculumByType[$type])) {
                $sortedCurriculumByType[$type] = $courses;
            }
        }

        // ── Build courses taken section (from student_enrollments) ──
        $coursesTaken = [];
        $curriculumCourseIds = $curriculum->pluck('course_id')->toArray();

        // Get unique completed/enrolled courses, latest enrollment per course
        $latestEnrollments = $enrollments
            ->groupBy('course_id')
            ->map(fn ($group) => $group->sortByDesc(fn ($e) => $e->semester?->term_code)->first())
            ->values();

        foreach ($latestEnrollments as $enrollment) {
            $course = $enrollment->course;
            if (!$course) continue;

            // Skip residency — handled separately
            if ($this->isResidencyCourse($course->course_code)) continue;

            // Determine course type from curriculum
            $curriculumEntry = $curriculum->first(fn ($pc) => $pc->course_id === $course->id);
            $typeLabel = 'Unclassified';
            $typeColor = '#999';
            $subGroup = null;
            if ($curriculumEntry) {
                $typeEnum = $curriculumEntry->course_type;
                $typeLabel = $typeEnum instanceof \App\Enums\CourseType
                    ? $typeEnum->label()
                    : ($typeEnum ? (\App\Enums\CourseType::tryFrom($typeEnum)?->label() ?? 'Other') : 'Other');
                $typeColor = \App\Filament\Pages\CurriculumMap::getTypeColor($typeLabel);
                $subGroup = $curriculumEntry->sub_group ?? $curriculumEntry->programMajor?->name;
            }

            $isInCurriculum = in_array($course->id, $curriculumCourseIds);

            $coursesTaken[] = [
                'enrollment_id' => $enrollment->id,
                'course_code' => $course->course_code,
                'course_name' => $course->course_name,
                'type' => $typeLabel,
                'type_color' => $typeColor,
                'sub_group' => $subGroup,
                'units' => $curriculumEntry?->units ?? $enrollment->units_earned,
                'grade' => $enrollment->grade,
                'status' => $enrollment->status,
                'term_taken' => $enrollment->semester?->label ?? $enrollment->semester?->term_code,
                'term_code_raw' => $enrollment->semester?->term_code,
                'is_in_curriculum' => $isInCurriculum,
            ];
        }

        // Group courses taken by type
        $coursesTakenByType = collect($coursesTaken)
            ->where('is_in_curriculum', true)
            ->groupBy('type')
            ->sortBy(function ($group, $type) use ($typeOrder) {
                $pos = array_search($type, $typeOrder);
                return $pos === false ? 99 : $pos;
            })
            ->toArray();

        // For Major/Specialization type, sub-group the courses
        foreach (['Major', 'Specialization'] as $groupableType) {
            if (isset($coursesTakenByType[$groupableType])) {
                $items = collect($coursesTakenByType[$groupableType]);
                $subGrouped = $items->groupBy(fn ($c) => $c['sub_group'] ?? '__ungrouped__')->toArray();
                $coursesTakenByType[$groupableType] = $subGrouped;
            }
        }

        // Unmatched courses (in enrollment but not in curriculum)
        $unmatchedCourses = collect($coursesTaken)
            ->where('is_in_curriculum', false)
            ->values()
            ->toArray();

        // Compute total progress
        $totalRequired = $program->total_units_required;
        $totalEarned = array_sum(array_column($typeProgress, 'earned_units'));

        // ── GWA Calculation ──
        $gradeMap = [
            '1.0' => 1.0, '1.00' => 1.0,
            '1.25' => 1.25,
            '1.5' => 1.5, '1.50' => 1.5,
            '1.75' => 1.75,
            '2.0' => 2.0, '2.00' => 2.0,
            '2.25' => 2.25,
            '2.5' => 2.5, '2.50' => 2.5,
            '2.75' => 2.75,
            '3.0' => 3.0, '3.00' => 3.0,
        ];
        $gwaWeightedSum = 0;
        $gwaUnitSum = 0;
        foreach ($enrollments->whereIn('course_id', $curriculumCourseIds) as $enrollment) {
            if ($enrollment->status !== 'completed' || !$enrollment->grade) continue;
            $numericGrade = $gradeMap[$enrollment->grade] ?? $enrollment->grade_numeric ?? null;
            if ($numericGrade === null || $numericGrade > 3.0) continue; // Skip INC, DRP, 4.0, 5.0
            $units = $enrollment->units_earned ?: ($enrollment->course ? $this->estimateCourseUnits($enrollment->course->course_code) : 3);
            $gwaWeightedSum += $numericGrade * $units;
            $gwaUnitSum += $units;
        }
        $gwa = $gwaUnitSum > 0 ? round($gwaWeightedSum / $gwaUnitSum, 4) : null;

        // Determine which curriculum types should be collapsible (>5 courses or sub-grouped)
        $collapsibleTypes = [];
        $inlineTypes = [];
        foreach ($sortedCurriculumByType as $type => $courses) {
            // For Major/Spec with sub-groups, check if it's a nested array
            if (in_array($type, ['Major', 'Specialization']) && is_array($courses) && !isset($courses[0])) {
                $collapsibleTypes[$type] = $courses;
            } elseif (is_array($courses) && count($courses) > 5) {
                $collapsibleTypes[$type] = $courses;
            } else {
                $inlineTypes[$type] = $courses;
            }
        }

        // Calculate unmatched/unspecified units
        $unmatchedUnits = collect($unmatchedCourses)->sum(fn ($c) => (int) ($c['units'] ?? 0));

        return [
            'student' => $student,
            'program' => $program,
            'type_progress' => array_values($typeProgress),
            'courses_taken_by_type' => $coursesTakenByType,
            'unmatched_courses' => $unmatchedCourses,
            'unmatched_units' => $unmatchedUnits,
            'curriculum_inline' => $inlineTypes,
            'curriculum_collapsible' => $collapsibleTypes,
            'total_required' => $totalRequired,
            'total_earned' => $totalEarned,
            'total_courses' => $curriculum->count(),
            'completed_courses' => count($completedCourseIds),
            'gwa' => $gwa,
        ];
    }

}
