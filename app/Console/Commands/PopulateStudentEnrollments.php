<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Enrollee;
use App\Models\EnrollmentCourse;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateStudentEnrollments extends Command
{
    protected $signature = 'student-enrollments:populate
        {--fresh : Delete all existing enrollment records before populating}';

    protected $description = 'Populate the student_enrollments table from enrollment_course_enrollee data';

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $placeholderCoursesCreated = 0;

    public function handle(): int
    {
        $this->info('Populating student_enrollments from enrollment data...');

        // Check prerequisite: students must exist
        $studentCount = Student::count();
        if ($studentCount === 0) {
            $this->error('No students found. Run `php artisan students:populate` first.');
            return self::FAILURE;
        }
        $this->info("Found {$studentCount} students in the students table.");

        if ($this->option('fresh')) {
            if ($this->confirm('This will DELETE all existing student enrollment records. Continue?')) {
                StudentEnrollment::query()->delete();
                $this->warn('All student enrollment records deleted.');
            } else {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        // Preload lookup data
        $studentLookup = Student::pluck('id', 'student_number')->toArray();
        $semesterLookup = DB::table('semesters')->pluck('id', 'term_code')->toArray();
        $courseLookup = Course::pluck('id', 'course_code')->toArray();

        // Create placeholder courses for non-standard course codes
        $this->createPlaceholderCourses($courseLookup);

        // Refresh course lookup after creating placeholders
        $courseLookup = Course::pluck('id', 'course_code')->toArray();

        // Get all enrollment data joined together
        $enrollments = DB::table('enrollment_course_enrollee as ece')
            ->join('enrollees as e', 'e.id', '=', 'ece.enrollee_id')
            ->join('enrollment_courses as ec', 'ec.id', '=', 'ece.enrollment_course_id')
            ->select([
                'e.student_number',
                'e.term_id',
                'e.total_units',
                'ec.course_code',
                'ece.notes',
            ])
            ->whereNotNull('e.student_number')
            ->where('e.student_number', '!=', '')
            ->orderBy('e.student_number')
            ->orderBy('e.term_id')
            ->get();

        $this->info("Found {$enrollments->count()} enrollment records to process.");

        // Group by student + course to determine completion status
        $grouped = $enrollments->groupBy(function ($row) {
            return $row->student_number . '|' . $row->course_code;
        });

        $bar = $this->output->createProgressBar($grouped->count());
        $bar->start();

        foreach ($grouped as $key => $records) {
            [$studentNumber, $courseCode] = explode('|', $key);

            $studentId = $studentLookup[$studentNumber] ?? null;
            $courseId = $courseLookup[$courseCode] ?? null;

            if (!$studentId) {
                $this->skipped += $records->count();
                $bar->advance();
                continue;
            }

            if (!$courseId) {
                // Course code not found even after placeholders — skip
                $this->skipped += $records->count();
                $bar->advance();
                continue;
            }

            // Sort records chronologically
            $sorted = $records->sortBy('term_id')->values();

            // Find the student's latest enrolled term
            $studentLatestTerm = DB::table('enrollees')
                ->where('student_number', $studentNumber)
                ->max('term_id');

            foreach ($sorted as $index => $record) {
                $semesterId = $semesterLookup[$record->term_id] ?? null;
                if (!$semesterId) {
                    $this->skipped++;
                    continue;
                }

                // Determine status:
                // - If enrolled in the student's latest term → "ongoing" (still taking it)
                // - If this is the last enrollment of this course AND not in latest term → "completed"
                // - If re-enrolled in same course later → earlier one is "incomplete"
                $isLast = ($index === $sorted->count() - 1);
                $isInLatestTerm = ($record->term_id == $studentLatestTerm);

                if ($isInLatestTerm) {
                    $status = 'enrolled';
                } elseif ($isLast) {
                    $status = 'completed';
                } elseif ($sorted->count() === 1) {
                    $status = 'completed';
                } else {
                    $status = 'incomplete';
                }

                // Calculate units per course
                $unitsEarned = ($status === 'completed') ? $this->estimateCourseUnits($courseCode, $studentNumber) : 0;

                // Upsert (unique: student_id + course_id + semester_id)
                $existing = StudentEnrollment::where('student_id', $studentId)
                    ->where('course_id', $courseId)
                    ->where('semester_id', $semesterId)
                    ->first();

                $data = [
                    'status' => $status,
                    'units_earned' => $unitsEarned,
                    'remarks' => $record->notes,
                ];

                if ($existing) {
                    $existing->update($data);
                    $this->updated++;
                } else {
                    StudentEnrollment::create(array_merge([
                        'student_id' => $studentId,
                        'course_id' => $courseId,
                        'semester_id' => $semesterId,
                    ], $data));
                    $this->created++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Results:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Enrollments created', $this->created],
                ['Enrollments updated', $this->updated],
                ['Records skipped', $this->skipped],
                ['Placeholder courses created', $this->placeholderCoursesCreated],
                ['Total enrollments', StudentEnrollment::count()],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Create placeholder courses for non-standard course codes found in enrollment data.
     */
    private function createPlaceholderCourses(array &$courseLookup): void
    {
        // Get all unique course codes from enrollment_courses
        $enrollmentCodes = EnrollmentCourse::pluck('course_code')->unique();

        // Find codes not in the courses table
        $missing = $enrollmentCodes->filter(fn ($code) => !isset($courseLookup[$code]));

        if ($missing->isEmpty()) {
            return;
        }

        $this->info("Creating {$missing->count()} placeholder courses for non-catalog codes...");

        foreach ($missing as $code) {
            $course = Course::firstOrCreate(
                ['course_code' => $code],
                [
                    'course_name' => $this->generatePlaceholderName($code),
                    'is_active' => false,
                ]
            );
            $courseLookup[$code] = $course->id;
            if ($course->wasRecentlyCreated) {
                $this->placeholderCoursesCreated++;
            }
        }
    }

    /**
     * Generate a human-readable name for a placeholder course.
     */
    private function generatePlaceholderName(string $code): string
    {
        $upper = strtoupper(trim($code));

        return match (true) {
            str_contains($upper, 'RESID') => 'Residency',
            $upper === 'SP' => 'Special Problem',
            str_contains($upper, 'SPECIAL') => 'Special Problem',
            str_contains($upper, 'THESIS') => 'Thesis',
            str_contains($upper, 'DISSERT') => 'Dissertation',
            default => "Non-catalog Course ({$code})",
        };
    }

    /**
     * Estimate units earned for a course based on program_courses mapping.
     * Falls back to 3 as a reasonable default for grad courses.
     */
    private function estimateCourseUnits(string $courseCode, string $studentNumber): int
    {
        // Try to find units from program_courses for this course
        $units = DB::table('program_courses as pc')
            ->join('courses as c', 'c.id', '=', 'pc.course_id')
            ->where('c.course_code', $courseCode)
            ->whereNotNull('pc.units')
            ->value('pc.units');

        if ($units) {
            return (int) $units;
        }

        // For non-standard courses, use 0 (not enough info)
        $upper = strtoupper($courseCode);
        if (in_array($upper, ['RESIDENCY', 'RESIDNCE', 'SP'])) {
            return 0;
        }

        // Default: 3 units for unresolvable courses
        return 3;
    }
}
