<?php

namespace App\Filament\Pages\StudentHistory;

use App\Models\Course;
use App\Models\Enrollee;
use App\Models\EnrollmentCourse;
use App\Models\Graduate;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

/**
 * HasStudentSearch trait extracted from StudentHistory.
 */
trait HasStudentSearch
{

    protected function getHeaderWidgets(): array
    {
        return [];
    }


    #[On('reactivateStudent')]
    public function reactivateStudent(string $studentNumber): void
    {
        $student = Student::where('student_number', $studentNumber)->first();
        if ($student) {
            $student->update(['student_status' => 'active']);
            $this->search();
            Notification::make()->title('Student status updated to Active')->success()->duration(3000)->send();
        }
    }


    public function getTitle(): string
    {
        if (!empty($this->studentInfo)) {
            $name = $this->studentInfo['full_name'] ?? $this->studentNumber;
            return "Student History: {$name}";
        }

        return 'List of Students';
    }


    public function getBreadcrumbs(): array
    {
        $crumbs = [
            url('/admin/list-of-students') => 'List of Students',
        ];

        if (!empty($this->studentInfo)) {
            $name = $this->studentInfo['full_name'] ?? $this->studentNumber;
            $crumbs[] = "Student History: {$name}";
        }

        return $crumbs;
    }




    public function updatedStudentNumber(): void
    {
        $q = trim($this->studentNumber);
        if (strlen($q) < 2) {
            $this->searchSuggestions = [];
            $this->showSuggestions = false;
            if (strlen($q) === 0) {
                $this->studentInfo = null;
            }
            return;
        }

        // Search by student_number OR name across students
        $studentSuggestions = Student::query()
            ->select('student_number', 'surname', 'given_name')
            ->where(function ($query) use ($q) {
                $query->where('student_number', 'like', "%{$q}%")
                    ->orWhere('surname', 'like', "%{$q}%")
                    ->orWhere('given_name', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(surname, ', ', given_name) LIKE ?", ["%{$q}%"]);
            })
            ->orderBy('surname')
            ->limit(15)
            ->get();

        $this->searchSuggestions = $studentSuggestions->map(fn ($s) => [
            'student_number' => $s->student_number,
            'display' => Enrollee::formatStudentNumber($s->student_number) . ' — ' . $s->surname . ', ' . $s->given_name,
        ])->unique('student_number')->values()->toArray();

        $this->showSuggestions = count($this->searchSuggestions) > 0;
    }


    public function selectStudent(string $studentNumber): void
    {
        $this->studentNumber = $studentNumber;
        $this->searchSuggestions = [];
        $this->showSuggestions = false;
        $this->search();
    }


    public function hideSuggestions(): void
    {
        $this->showSuggestions = false;
    }


    public function backToList(): void
    {
        $this->studentNumber = '';
        $this->studentInfo = null;
        $this->searchSuggestions = [];
        $this->showSuggestions = false;

        $this->redirect(static::getUrl());
    }


    /**
     * Memoized student record to avoid repeated DB queries within a single request.
     */
    protected ?Student $cachedStudentRecord = null;
    protected ?string $cachedStudentNumber = null;

    /**
     * Clear the memoized student record (call after mutations).
     */
    public function resetStudentCache(): void
    {
        $this->cachedStudentRecord = null;
        $this->cachedStudentNumber = null;
    }

    public function search(): void
    {
        // Bust the memoized student cache on every new search
        $this->resetStudentCache();

        if (empty($this->studentNumber)) {
            $this->studentInfo = null;
            return;
        }

        // Find student record directly from the students table
        $student = Student::where('student_number', $this->studentNumber)->first();

        // If not found in students table, try to create from enrollee data
        if (!$student) {
            $student = $this->ensureStudentRecord($this->studentNumber);
        }

        if (!$student) {
            $this->studentInfo = null;
            return;
        }

        // Try to sync enrollments, but don't let it break the search
        try {
            $this->ensureStudentEnrollments($student);
        } catch (\Throwable $e) {
            // Log but don't block — the student info should still display
            \Illuminate\Support\Facades\Log::warning('ensureStudentEnrollments failed: ' . $e->getMessage());
        }

        // Find the latest enrollment record for this student number for fallback data
        $latest = Enrollee::where('student_number', $this->studentNumber)
            ->orderBy('term_id', 'desc')
            ->first();

        // Count total terms enrolled
        $totalTerms = Enrollee::where('student_number', $this->studentNumber)
            ->distinct('term_id')
            ->count('term_id');

        $this->studentInfo = [
            'name' => $this->fixEncoding($student->full_name ?? $latest?->full_name),
            'student_number' => $student->student_number,
            'total_terms' => $totalTerms,
            'email' => $student->email ?? $latest?->email,
            'up_email' => $student->up_email ?? null,
            'alternative_email' => $student->alternative_email ?? null,
            'sex' => $student->sex ?? $latest?->sex,
            'birthdate' => $student->birthdate?->format('F j, Y') ?? $latest?->birthdate?->format('F j, Y'),
            'nationality' => $student->nationality ?? $latest?->nationality,
            'marital_status' => $student->marital_status ?? $latest?->marital_status,
            'contact_number' => $student->contact_number ?? null,
            'primary_contact_number' => $student->primary_contact_number ?? null,
            'alternative_contact_number' => $student->alternative_contact_number ?? null,
            'address' => $student->address ?? null,
            'country_of_origin' => $student->country_of_origin ?? null,
            'social_facebook' => $student->social_facebook ?? null,
            'social_linkedin' => $student->social_linkedin ?? null,
            'social_other' => $student->social_other ?? null,
            'institution_affiliated' => $student->institution_affiliated ?? null,
            'student_status' => $student->student_status ?? 'active',
            'applicant_status' => $student->applicant_status ?? null,
            'admission_semester' => $student->admissionSemester?->label ?? null,
            'admission_date' => $student->admission_date?->format('F j, Y') ?? null,
            'registration_adviser_name' => $student->registrationAdviser?->full_name ?? null,
            'registration_adviser_designation' => $student->registrationAdviser?->designation ?? null,
            'registration_adviser_appointed_date' => $student->registration_adviser_appointed_date?->format('F j, Y') ?? null,
            'committee_members' => $student->committeeMembers?->load('faculty', 'termStart', 'termEnd')->map(fn ($cm) => [
                'name' => $cm->faculty?->full_name ?? 'Unknown',
                'designation' => $cm->faculty?->designation ?? null,
                'role' => $cm->role_label,
                'member_role' => $cm->member_role,
                'appointed_date' => $cm->appointed_date?->format('F j, Y'),
                'term_start' => $cm->termStart?->label ?? null,
                'term_start_code' => $cm->termStart?->term_code ?? null,
                'term_end' => $cm->termEnd?->label ?? null,
                'term_end_code' => $cm->termEnd?->term_code ?? null,
            ])->toArray() ?? [],
        ];

        // Check graduate status — load ALL graduation records
        $graduates = Graduate::where('student_number', $this->studentNumber)->with('program')->get();
        $graduationRecords = [];
        if ($graduates->isNotEmpty()) {
            // Pre-load all programs with majors for efficient matching
            $allPrograms = \App\Models\Program::with('majors')->get();
            // Pre-load all faculty for committee matching
            $allFaculty = \App\Models\Faculty::all();
            // Pre-load all semesters for efficient term resolution
            $allSemesters = \App\Models\Semester::with('academicYear')->get();

            foreach ($graduates as $graduate) {
                // Load from normalized table first, fallback to legacy flat columns
                $normalizedMembers = $graduate->committeeMembers()->with('faculty')->get();
                $committeeData = [];

                if ($normalizedMembers->count() > 0) {
                    $committeeData = $normalizedMembers->map(function ($gcm) use ($allSemesters) {
                        $name = $gcm->faculty ? $gcm->faculty->full_name : ($gcm->name ?? 'Unknown');
                        $designation = $gcm->faculty?->designation;

                        // Resolve term labels
                        $termStart = null;
                        $termEnd = null;
                        if ($gcm->term_start_id) {
                            $sem = $allSemesters->firstWhere('id', $gcm->term_start_id);
                            $termStart = $sem?->label;
                        }
                        if ($gcm->term_end_id) {
                            $sem = $allSemesters->firstWhere('id', $gcm->term_end_id);
                            $termEnd = $sem?->label;
                        }

                        return [
                            'role' => $gcm->role,
                            'name' => $name,
                            'designation' => $designation,
                            'appointed_date' => $gcm->appointed_date?->format('Y-m-d'),
                            'formatted_appointed_date' => $gcm->appointed_date?->format('F j, Y'),
                            'term_start' => $termStart,
                            'term_end' => $termEnd,
                            'role_label' => $gcm->role,
                        ];
                    })->toArray();
                } else {
                    // Fallback to legacy flat columns
                    $legacyMembers = [
                        ['role' => 'Chair', 'name' => $graduate->chair],
                        ['role' => 'Co-Chair', 'name' => $graduate->co_chair],
                        ['role' => 'Member', 'name' => $graduate->member1],
                        ['role' => 'Member', 'name' => $graduate->member2],
                        ['role' => 'Member', 'name' => $graduate->member3],
                        ['role' => 'Member', 'name' => $graduate->member4],
                        ['role' => 'Member', 'name' => $graduate->member5],
                    ];
                    foreach ($legacyMembers as $lm) {
                        $name = trim($lm['name'] ?? '');
                        if (!empty($name) && !in_array(mb_strtolower($name), ['n/a', 'na', 'none', '-', ''])) {
                            // Try to match to faculty for designation
                            $matchedFaculty = $allFaculty->first(function ($f) use ($name) {
                                return mb_strtolower($f->full_name) === mb_strtolower($name);
                            });

                            $committeeData[] = [
                                'role' => $lm['role'],
                                'name' => $matchedFaculty ? $matchedFaculty->full_name : $name,
                                'designation' => $matchedFaculty?->designation,
                                'appointed_date' => null,
                                'role_label' => $lm['role'],
                            ];
                        }
                    }
                }

                $semesterLabel = $graduate->semester_graduated;
                if ($semesterLabel && is_numeric($semesterLabel)) {
                    $sem = \App\Models\Semester::where('term_code', $semesterLabel)->first();
                    if ($sem) {
                        $semesterLabel = $sem->label;
                    }
                }

                // Data is now properly resolved in the database.
                // Just read directly — no runtime resolution needed.
                $graduationRecords[] = [
                    'id' => $graduate->id,
                    'program_id' => $graduate->program_id,
                    'program_name' => $graduate->program_name,
                    'program_code' => $graduate->degree,
                    'semester_graduated' => $semesterLabel,
                    'semester_raw' => $graduate->semester_raw,
                    'degree' => $graduate->degree,
                    'major' => $graduate->major,
                    'country_of_origin' => $graduate->country_of_origin ? ucwords(mb_strtolower(trim($graduate->country_of_origin))) : null,
                    'committee_data' => collect($committeeData)->map(function ($cm) {
                        if (!empty($cm['appointed_date']) && empty($cm['formatted_appointed_date'])) {
                            try {
                                $cm['formatted_appointed_date'] = \Carbon\Carbon::parse($cm['appointed_date'])->format('F j, Y');
                            } catch (\Exception $e) {
                                $cm['formatted_appointed_date'] = $cm['appointed_date'];
                            }
                        }
                        // Ensure role_label exists for export consistency
                        if (empty($cm['role_label'])) {
                            $cm['role_label'] = $cm['role'] ?? 'Member';
                        }
                        return $cm;
                    })->toArray(),
                ];
            }
        }

        $this->studentInfo = array_merge($this->studentInfo, [
            'graduation_records' => $graduationRecords,
            'is_graduate' => !empty($graduationRecords),
        ]);

        // Reset editing state on new search
        $this->editingMilestoneId = null;
        $this->deletingMilestoneId = null;
        $this->editingAoId = null;
        $this->deletingAoId = null;
        $this->editingGraduateId = null;
        $this->showMilestoneForm = false;
        $this->showAoForm = false;
    }


    // ══════════════════════════════════════════════════════════
    // ── Concern 1: Auto-population ──
    // ══════════════════════════════════════════════════════════

    /**
     * Ensure a Student record exists for the given student number.
     * Creates one from Enrollee data if it doesn't exist.
     */
    public function ensureStudentRecord(string $studentNumber): ?Student
    {
        $existing = Student::withTrashed()->where('student_number', $studentNumber)->first();
        if ($existing) {
            return $existing;
        }

        $latest = Enrollee::where('student_number', $studentNumber)
            ->orderByDesc('term_id')
            ->first();

        if (!$latest) {
            return null;
        }

        // Derive admission semester (disabled as per user request to fill manually)
        $admissionSemesterId = null;
        $admissionDate = null;

        // Sum total units
        $totalUnits = (int) Enrollee::where('student_number', $studentNumber)->sum('total_units');

        // Match program
        $programId = $this->matchProgramForStudent($latest->degree_program);

        // Check graduate status
        $graduate = Graduate::where('student_number', $studentNumber)->first();
        $status = $graduate ? 'graduated' : 'active';

        // Build full name — fix encoding for ñ and other special chars
        $lastName = $this->fixEncoding($latest->last_name);
        $firstName = $this->fixEncoding($latest->first_name);
        $middleName = $this->fixEncoding($latest->middle_name);

        $fullName = Student::buildFullName($lastName, $firstName, $middleName);

        return Student::create([
            'student_number' => $studentNumber,
            'surname' => $lastName,
            'given_name' => $firstName,
            'middle_name' => $middleName,
            'full_name' => $fullName,
            'email' => $latest->email ?? "{$studentNumber}@up.edu.ph",
            'birthdate' => $latest->birthdate,
            'sex' => $this->normalizeSex($latest->sex),
            'marital_status' => $this->normalizeMaritalStatus($latest->marital_status),
            'nationality' => $latest->nationality ?: 'Filipino',
            'country_of_origin' => $graduate?->country_of_origin,
            'program_id' => $programId,
            'admission_semester_id' => $admissionSemesterId,
            'admission_date' => $admissionDate,
            'student_status' => $status,
            'total_units_earned' => $totalUnits,
        ]);
    }


    /**
     * Ensure StudentEnrollment records exist for the student.
     * Parses enrollment data if none exist.
     */
    public function ensureStudentEnrollments(Student $student): void
    {
        // Incremental sync: firstOrCreate below handles deduplication,
        // so we always check for new enrollee records to sync.
        $existingCount = $student->enrollments()->count();

        $semesterLookup = DB::table('semesters')->pluck('id', 'term_code')->toArray();

        // Build a lookup: semester_id → student_program_id
        $this->ensureStudentPrograms($student);
        $student->load('studentPrograms'); // Refresh after additive sync
        $programBySemester = [];
        foreach ($student->studentPrograms as $sp) {
            foreach ($sp->enrollments()->pluck('semester_id')->toArray() as $sid) {
                $programBySemester[$sid] = $sp->id;
            }
        }
        // Also build from enrollee data for new enrollments
        $enrolleePrograms = Enrollee::where('student_number', $student->student_number)
            ->whereNotNull('degree_program')
            ->where('degree_program', '!=', '')
            ->get(['term_id', 'degree_program']);
        // Key by raw_degree_name for precise matching (supports same program, different major)
        $spByRawDegree = $student->studentPrograms->keyBy('raw_degree_name');
        foreach ($enrolleePrograms as $ep) {
            $semId = $semesterLookup[$ep->term_id] ?? null;
            if (!$semId) continue;
            if (isset($spByRawDegree[$ep->degree_program])) {
                $programBySemester[$semId] = $spByRawDegree[$ep->degree_program]->id;
            }
        }

        // Get all enrollment data for this student
        $enrollments = DB::table('enrollment_course_enrollee as ece')
            ->join('enrollees as e', 'e.id', '=', 'ece.enrollee_id')
            ->join('enrollment_courses as ec', 'ec.id', '=', 'ece.enrollment_course_id')
            ->select(['e.student_number', 'e.term_id', 'ec.course_code', 'ece.notes'])
            ->where('e.student_number', $student->student_number)
            ->orderBy('e.term_id')
            ->get();

        if ($enrollments->isEmpty()) {
            return;
        }

        // Group by course_code to determine completion status
        $grouped = $enrollments->groupBy('course_code');

        // Determine the current/active term from enrollee data (semesters table may not have latest)
        $currentTermCode = DB::table('enrollees')->max('term_id');

        foreach ($grouped as $courseCode => $records) {
            // Ensure Course exists
            $course = Course::firstOrCreate(
                ['course_code' => $courseCode],
                [
                    'course_name' => $this->generatePlaceholderName($courseCode),
                    'is_active' => false,
                ]
            );

            $sorted = $records->sortBy('term_id')->values();

            // Detect thesis/dissertation repeated enrollment
            $isThesisLike = $this->isThesisLikeCourse($courseCode);
            $hasMultipleEnrollments = $sorted->count() > 1;

            foreach ($sorted as $index => $record) {
                $semesterId = $semesterLookup[$record->term_id] ?? null;
                if (!$semesterId) continue;

                $isLast = ($index === $sorted->count() - 1);
                $isCurrentTerm = ($record->term_id == $currentTermCode);

                if ($isCurrentTerm) {
                    $status = 'enrolled';
                } elseif ($isLast && $isThesisLike && $hasMultipleEnrollments) {
                    $hasGraduated = Graduate::where('student_number', $student->student_number)->exists();
                    $status = $hasGraduated ? 'completed' : 'enrolled';
                } elseif ($sorted->count() === 1 || $isLast) {
                    $status = 'completed';
                } else {
                    $status = 'incomplete';
                }

                $units = $this->estimateCourseUnits($courseCode);

                StudentEnrollment::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'semester_id' => $semesterId,
                    ],
                    [
                        'student_program_id' => $programBySemester[$semesterId] ?? null,
                        'status' => $status,
                        'units_earned' => $status === 'completed' ? $units : 0,
                        'remarks' => $record->notes,
                    ]
                );
            }
        }
    }


    // ══════════════════════════════════════════════════════════
    // ── Academic Progress Tracking (Concern 2: sub-grouping) ──
    // ══════════════════════════════════════════════════════════

    /**
     * Get the Student model record for the searched student.
     * Memoized to avoid redundant DB queries within a request.
     */
    public function getStudentRecord(): ?Student
    {
        if (empty($this->studentNumber)) {
            return null;
        }

        // Return cached result if the student number hasn't changed
        if ($this->cachedStudentRecord && $this->cachedStudentNumber === $this->studentNumber) {
            return $this->cachedStudentRecord;
        }

        $this->cachedStudentNumber = $this->studentNumber;
        $this->cachedStudentRecord = Student::withTrashed()
            ->where('student_number', $this->studentNumber)
            ->first();

        return $this->cachedStudentRecord;
    }

}
