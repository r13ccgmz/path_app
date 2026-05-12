<?php

namespace App\Filament\Pages\StudentHistory;

use App\Models\Enrollee;
use App\Models\EnrollmentCourse;
use App\Models\NormalizationRule;
use App\Models\ProgramCourse;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProgram;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * HasEnrollmentManagement trait extracted from StudentHistory.
 */
trait HasEnrollmentManagement
{

    /**
     * Toggle the status of a student enrollment record.
     */
    public function toggleEnrollmentStatus(int $enrollmentId): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        $enrollment = StudentEnrollment::where('id', $enrollmentId)
            ->where('student_id', $student->id)
            ->first();

        if (!$enrollment) return;

        // Cycle: enrolled → completed → incomplete → enrolled
        $newStatus = match ($enrollment->status) {
            'completed' => 'incomplete',
            'incomplete' => 'enrolled',
            'enrolled' => 'completed',
            default => 'completed',
        };

        $enrollment->update(['status' => $newStatus]);

        Notification::make()
            ->title('Status Updated')
            ->body("{$enrollment->course->course_code} marked as {$newStatus}")
            ->success()
            ->duration(3000)
            ->send();
    }


    /**
     * Delete a student enrollment record.
     */
    public function confirmDeleteEnrollment(int $enrollmentId): void
    {
        $this->deletingEnrollmentId = $enrollmentId;
    }


    /**
     * Recalculates the total units for an enrollee record by summing the actual units_earned
     * from their student_enrollments for that specific term.
     */
    private function recalculateEnrolleeUnits(Enrollee $enrollee, $studentId, $semesterId): void
    {
        $coursesArray = array_map('trim', explode(',', $enrollee->courses_enrolled ?? ''));
        $coursesArray = array_filter($coursesArray);
        
        if (empty($coursesArray)) {
            $enrollee->delete();
            return;
        }

        $newUnits = 0;
        foreach ($coursesArray as $courseCode) {
            $courseId = DB::table('courses')->where('course_code', $courseCode)->value('id');
            if ($courseId && $semesterId) {
                $earned = StudentEnrollment::where('student_id', $studentId)
                    ->where('semester_id', $semesterId)
                    ->where('course_id', $courseId)
                    ->value('units_earned');
                    
                if ($earned !== null) {
                    $newUnits += $earned;
                } else {
                    $newUnits += $this->estimateCourseUnits($courseCode);
                }
            } else {
                $newUnits += $this->estimateCourseUnits($courseCode);
            }
        }
        
        $enrollee->courses_enrolled = implode(', ', $coursesArray);
        $enrollee->total_units = $newUnits;
        $enrollee->save();
    }


    public function deleteEnrollment(): void
    {
        $student = $this->getStudentRecord();
        if (!$student || !$this->deletingEnrollmentId) return;

        $enrollment = StudentEnrollment::where('id', $this->deletingEnrollmentId)
            ->where('student_id', $student->id)
            ->with(['course', 'semester'])
            ->first();

        if ($enrollment) {
            $code = $enrollment->course->course_code;
            $courseId = $enrollment->course_id;
            $studentProgramId = $enrollment->student_program_id;

            // Sync removal to the Enrollee table
            if ($enrollment->semester) {
                $termCode = $enrollment->semester->term_code;
                $enrollee = Enrollee::where('student_number', $student->student_number)
                    ->where('term_id', $termCode)
                    ->first();

                if ($enrollee) {
                    $coursesArray = array_map('trim', explode(',', $enrollee->courses_enrolled ?? ''));
                    $coursesArray = array_filter($coursesArray, fn($c) => trim($c) !== trim($code));
                    $enrollee->courses_enrolled = implode(', ', $coursesArray);
                    
                    $this->recalculateEnrolleeUnits($enrollee, $student->id, $enrollment->semester_id);
                }
            }

            // Delete the enrollment FIRST, then clean up curriculum
            $enrollment->delete();

            // Clean up manually-classified curriculum entries (after deletion so the check works)
            $this->cleanupManualCurriculumEntries($student, [$courseId]);

            // Check if the parent student_program is now orphaned (no enrollments left)
            if ($studentProgramId) {
                $remaining = StudentEnrollment::where('student_program_id', $studentProgramId)->count();
                if ($remaining === 0) {
                    $sp = \App\Models\StudentProgram::find($studentProgramId);
                    if ($sp) {
                        // Clear students.program_id if it matches
                        if ($student->program_id === $sp->program_id) {
                            $student->program_id = null;
                            $student->save();
                        }
                        $sp->delete();
                    }
                }
            }

            Notification::make()
                ->title('Enrollment Deleted')
                ->body("{$code} removed from records")
                ->warning()
                ->duration(3000)
                ->send();
        }

        $this->deletingEnrollmentId = null;
    }


    public function cancelDeleteEnrollment(): void
    {
        $this->deletingEnrollmentId = null;
    }


    /**
     * Header actions — 'addEnrollment' removed and migrated to Table action natively.
     */



    /**
     * Classify an unmatched course by assigning it to the student's curriculum.
     */
    public function classifyUnmatchedCourse(int $enrollmentId, ?string $courseType): void
    {
        if (empty($courseType)) {
            return;
        }

        $student = $this->getStudentRecord();
        if (!$student) return;

        $enrollment = StudentEnrollment::where('id', $enrollmentId)
            ->where('student_id', $student->id)
            ->first();

        if (!$enrollment) return;

        // Derive program_id from the enrollment's student_program relationship
        $studentProgram = $enrollment->studentProgram ?? \App\Models\StudentProgram::where('student_id', $student->id)->first();
        $programId = $studentProgram?->program_id ?? $student->program_id;

        if (!$programId) {
            Notification::make()
                ->title('Cannot Classify')
                ->body('No program found for this student. Please assign a program first.')
                ->danger()
                ->duration(4000)
                ->send();
            return;
        }

        // Create a program_course entry linking this course to the student's program
        ProgramCourse::firstOrCreate(
            [
                'program_id' => $programId,
                'course_id' => $enrollment->course_id,
            ],
            [
                'course_type' => $courseType,
                'program_major_id' => $studentProgram?->program_major_id ?? $student->program_major_id,
                'is_required' => false,
                'units' => $enrollment->units_earned ?: $this->estimateCourseUnits($enrollment->course->course_code),
            ]
        );

        Notification::make()
            ->title('Course Classified')
            ->body("{$enrollment->course->course_code} added as " . ucfirst(str_replace('_', ' ', $courseType)))
            ->success()
            ->duration(3000)
            ->send();

        // Refresh the page data so it moves out of Unspecified Courses immediately
        $this->search();
    }


    /**
     * Dismiss an unmatched course (delete the enrollment record).
     */
    public function dismissUnmatchedCourse(int $enrollmentId): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        $enrollment = StudentEnrollment::where('id', $enrollmentId)
            ->where('student_id', $student->id)
            ->with(['course', 'semester'])
            ->first();

        if ($enrollment) {
            $code = $enrollment->course->course_code;
            $courseId = $enrollment->course_id;
            $termCode = $enrollment->semester?->term_code;

            // Sync removal to the Enrollee table
            if ($termCode) {
                $enrollee = Enrollee::where('student_number', $student->student_number)
                    ->where('term_id', $termCode)
                    ->first();

                if ($enrollee) {
                    $coursesArray = array_map('trim', explode(',', $enrollee->courses_enrolled ?? ''));
                    $coursesArray = array_filter($coursesArray, fn($c) => trim($c) !== trim($code));
                    $enrollee->courses_enrolled = implode(', ', $coursesArray);
                    
                    $this->recalculateEnrolleeUnits($enrollee, $student->id, $enrollment->semester_id);
                }
            }

            // Delete the enrollment FIRST, then clean up curriculum
            $enrollment->delete();

            // Clean up manually-classified curriculum entries (after deletion so the check works)
            $this->cleanupManualCurriculumEntries($student, [$courseId]);

            Notification::make()
                ->title('Course Dismissed')
                ->body("{$code} removed from records")
                ->warning()
                ->duration(3000)
                ->send();

            // Refresh the page data so it moves out of Unspecified Courses immediately
            $this->search();
        }
    }


    /**
     * Clean up manually-classified program_courses entries when courses are removed from enrollment.
     * Only removes entries where is_required = false (manually classified, not from official curriculum)
     * and the student has no remaining enrollments for that course in that program.
     */
    private function cleanupManualCurriculumEntries($student, array $courseIds): void
    {
        if (empty($courseIds)) return;

        // Get all student programs
        $studentPrograms = \App\Models\StudentProgram::where('student_id', $student->id)->get();

        foreach ($studentPrograms as $sp) {
            foreach ($courseIds as $courseId) {
                // Check if there are any remaining enrollments for this course under this program
                $hasRemainingEnrollment = StudentEnrollment::where('student_id', $student->id)
                    ->where('student_program_id', $sp->id)
                    ->where('course_id', $courseId)
                    ->exists();

                if (!$hasRemainingEnrollment) {
                    // Remove the manually-classified curriculum entry (is_required = false only)
                    ProgramCourse::where('program_id', $sp->program_id)
                        ->where('course_id', $courseId)
                        ->where('is_required', false)
                        ->delete();
                }
            }
        }
    }


    // ══════════════════════════════════════════════════════════
    // ── Name History (past name variations from enrollment data) ──
    // ══════════════════════════════════════════════════════════

    /**
     * Get distinct name variations for the current student from enrollee records.
     * Returns an array of ['name' => ..., 'first_term' => ..., 'last_term' => ...].
     */
    public function getNameHistory(): array
    {
        if (empty($this->studentNumber)) return [];

        $records = Enrollee::where('student_number', $this->studentNumber)
            ->select('last_name', 'first_name', 'middle_name', DB::raw('MIN(term_id) as first_term'), DB::raw('MAX(term_id) as last_term'))
            ->groupBy('last_name', 'first_name', 'middle_name')
            ->orderBy('first_term')
            ->get();

        if ($records->count() <= 1) return []; // No name changes

        return $records->map(function ($r) {
            $name = implode(', ', [$r->last_name, $r->first_name]);
            if ($r->middle_name) $name .= ' ' . $r->middle_name;
            return [
                'name' => $this->fixEncoding($name),
                'first_term' => $r->first_term,
                'last_term' => $r->last_term,
            ];
        })->toArray();
    }


    /**
     * Update a specific field on a student enrollment (e.g., grade, status).
     */
    public function updateEnrollmentField(int $enrollmentId, string $field, ?string $value): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        $enrollment = StudentEnrollment::where('id', $enrollmentId)
            ->where('student_id', $student->id)
            ->first();

        if (!$enrollment) return;

        $allowedFields = ['status', 'grade', 'term', 'units_earned', 'course_type_override'];
        if (!in_array($field, $allowedFields)) return;

        $updateData = [];
        $fieldDisplay = ucfirst(str_replace('_', ' ', $field));

        $oldSemesterId = $enrollment->semester_id;
        $oldUnits = $enrollment->units_earned;
        $courseCode = $enrollment->course->course_code;

        if ($field === 'term') {
            if ($value) {
                // Find semester_id for this term code
                $semesterId = \Illuminate\Support\Facades\DB::table('semesters')
                    ->where('term_code', $value)
                    ->value('id');
                
                if (!$semesterId) {
                    Notification::make()
                        ->title('Invalid Term')
                        ->body("Term '{$value}' does not exist.")
                        ->danger()
                        ->duration(3000)
                        ->send();
                    return;
                }
                $updateData['semester_id'] = $semesterId;
            } else {
                $updateData['semester_id'] = null;
            }
        } elseif ($field === 'units_earned') {
            $updateData['units_earned'] = ($value === '' || $value === null) ? 0 : (int)$value;
        } elseif ($field === 'course_type_override') {
            $updateData['course_type_override'] = ($value === '' || $value === null) ? null : $value;
        } else {
            $updateData[$field] = $value ?: null;
        }

        // When grade is updated, also set grade_numeric for GWA calculations
        if ($field === 'grade' && $value) {
            $numericMap = [
                '1.0' => 1.0, '1.25' => 1.25, '1.5' => 1.5, '1.75' => 1.75,
                '2.0' => 2.0, '2.25' => 2.25, '2.5' => 2.5, '2.75' => 2.75,
                '3.0' => 3.0, '4.0' => 4.0, '5.0' => 5.0,
            ];
            $updateData['grade_numeric'] = $numericMap[$value] ?? null;
        }

        $enrollment->update($updateData);

        // Sync changes to Enrollee records
        if ($field === 'term' || $field === 'units_earned') {
            if ($field === 'term' && isset($updateData['semester_id']) && $oldSemesterId !== $updateData['semester_id']) {
                // Remove course from old enrollee record
                if ($oldSemesterId) {
                    $oldTermCode = \Illuminate\Support\Facades\DB::table('semesters')->where('id', $oldSemesterId)->value('term_code');
                    if ($oldTermCode) {
                        $oldEnrollee = Enrollee::where('student_number', $student->student_number)
                            ->where('term_id', $oldTermCode)
                            ->first();
                        if ($oldEnrollee) {
                            $coursesArray = array_map('trim', explode(',', $oldEnrollee->courses_enrolled ?? ''));
                            $coursesArray = array_filter($coursesArray, fn($c) => trim($c) !== trim($courseCode));
                            $oldEnrollee->courses_enrolled = implode(', ', $coursesArray);
                            $this->recalculateEnrolleeUnits($oldEnrollee, $student->id, $oldSemesterId);

                            // Detach course from pivot table
                            $ecToDetach = EnrollmentCourse::where('course_code', $courseCode)->first();
                            if ($ecToDetach) {
                                $oldEnrollee->enrollmentCourses()->detach($ecToDetach->id);
                            }
                        }
                    }
                }

                // Add course to new enrollee record
                $newTermCode = $value;
                if ($newTermCode) {
                    $newEnrollee = Enrollee::firstOrCreate(
                        [
                            'student_number' => $student->student_number,
                            'term_id' => $newTermCode,
                        ],
                        [
                            'campus_id' => 1,
                            'last_name' => $student->surname,
                            'first_name' => $student->given_name,
                            'middle_name' => $student->middle_name,
                            'sex' => $student->sex,
                            'source' => 'manual',
                        ]
                    );

                    $coursesArray = array_map('trim', explode(',', $newEnrollee->courses_enrolled ?? ''));
                    $coursesArray = array_filter($coursesArray);
                    if (!in_array($courseCode, $coursesArray)) {
                        $coursesArray[] = $courseCode;
                    }
                    $newEnrollee->courses_enrolled = implode(', ', $coursesArray);
                    $this->recalculateEnrolleeUnits($newEnrollee, $student->id, $updateData['semester_id']);

                    // Attach course to pivot table so badges appear in the Enrollees list
                    $ecToAttach = EnrollmentCourse::firstOrCreate(['course_code' => $courseCode]);
                    $newEnrollee->enrollmentCourses()->syncWithoutDetaching([$ecToAttach->id]);
                }
            } elseif ($field === 'units_earned') {
                // Recalculate units for current enrollee record
                if ($enrollment->semester) {
                    $termCode = $enrollment->semester->term_code;
                    $enrollee = Enrollee::where('student_number', $student->student_number)
                        ->where('term_id', $termCode)
                        ->first();
                    if ($enrollee) {
                        $this->recalculateEnrolleeUnits($enrollee, $student->id, $enrollment->semester_id);
                    }
                }
            }
        }

        Notification::make()
            ->title(ucfirst($field) . ' Updated')
            ->body("{$enrollment->course->course_code}: {$field} set to " . ($value ?: 'none'))
            ->success()
            ->duration(2000)
            ->send();

        // Refresh the page data so UI computations (units, progress, etc.) update immediately
        $this->search();
    }

    public function deleteProgram(int $studentProgramId)
    {
        $sp = \App\Models\StudentProgram::find($studentProgramId);
        if (!$sp) return;

        $student = $this->getStudentRecord();

        // Collect the semester IDs and course IDs from the student_enrollments being deleted
        $enrollments = \App\Models\StudentEnrollment::where('student_program_id', $studentProgramId)
            ->with(['semester', 'course'])
            ->get();

        // Group by semester to clean up enrollees table
        if ($student) {
            $bySemester = $enrollments->groupBy(fn ($e) => $e->semester?->term_code);
            foreach ($bySemester as $termCode => $termEnrollments) {
                if (!$termCode) continue;

                $courseCodesToRemove = $termEnrollments->map(fn ($e) => $e->course?->course_code)->filter()->toArray();

                $enrollee = Enrollee::where('student_number', $student->student_number)
                    ->where('term_id', $termCode)
                    ->first();

                if ($enrollee) {
                    $existingCourses = array_map('trim', explode(',', $enrollee->courses_enrolled ?? ''));
                    $remainingCourses = array_diff($existingCourses, $courseCodesToRemove);

                    if (empty(array_filter($remainingCourses))) {
                        // No courses left for this term, delete the entire enrollee record
                        $enrollee->delete();
                    } else {
                        // Recalculate units for remaining courses
                        $newUnits = 0;
                        foreach ($remainingCourses as $code) {
                            $newUnits += $this->estimateCourseUnits($code);
                        }
                        $enrollee->courses_enrolled = implode(', ', $remainingCourses);
                        $enrollee->total_units = $newUnits;
                        $enrollee->save();
                    }
                }
            }
        }

        // Clean up manually-classified curriculum entries
        if ($student) {
            $courseIdsToClean = $enrollments->pluck('course_id')->unique()->toArray();
            // For deleteProgram, directly remove is_required=false entries for this program
            ProgramCourse::where('program_id', $sp->program_id)
                ->whereIn('course_id', $courseIdsToClean)
                ->where('is_required', false)
                ->delete();
        }

        // Delete associated enrollments first to prevent orphans
        \App\Models\StudentEnrollment::where('student_program_id', $studentProgramId)->delete();
        
        $programName = $sp->program?->code ?? 'Program';

        // Also clear students.program_id if it matches the deleted program
        if ($student && $student->program_id === $sp->program_id) {
            $student->program_id = null;
            $student->save();
        }

        $sp->delete();

        // Re-load the page data so the UI reflects the removal immediately
        $this->search();

        Notification::make()
            ->title('Program Removed')
            ->body("{$programName} and its enrollments have been removed from this student.")
            ->success()
            ->send();
    }


    /**
     * Remove a fallback program (one stored on students.program_id, not in student_programs table).
     */
    public function removeFallbackProgram(): void
    {
        $student = $this->getStudentRecord();
        if (!$student || !$student->program_id) return;

        $programName = $student->program?->code ?? 'Program';
        $student->program_id = null;
        $student->save();

        // Re-load the page data so the UI reflects the removal immediately
        $this->search();

        Notification::make()
            ->title('Program Removed')
            ->body("{$programName} has been unlinked from this student.")
            ->success()
            ->send();
    }

}
