<?php

namespace Database\Seeders;

use App\Models\Enrollee;
use App\Models\Graduate;
use App\Models\Semester;
use App\Models\Student;
use App\Services\ProgramMatcher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Populate the `students` table from ALL unique enrollees.
 * This is a prerequisite for StudentProgramSeeder.
 * Safe to re-run — uses updateOrCreate.
 */
class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $matcher = ProgramMatcher::instance();

        // Get all unique student numbers from enrollees
        $uniqueStudents = Enrollee::query()
            ->select('student_number')
            ->distinct()
            ->pluck('student_number');

        $this->command->info("Found {$uniqueStudents->count()} unique students in enrollees.");

        $created = 0;
        $updated = 0;

        foreach ($uniqueStudents as $studentNumber) {
            // Get the latest enrollee record (has the most recent name/email/program)
            $latestEnrollee = Enrollee::where('student_number', $studentNumber)
                ->orderByDesc('term_id')
                ->first();

            if (!$latestEnrollee) continue;

            // Get the earliest enrollee record (for admission data)
            $firstEnrollee = Enrollee::where('student_number', $studentNumber)
                ->orderBy('term_id')
                ->first();

            // Parse name from enrollee
            $fullName = $latestEnrollee->full_name ?? ($latestEnrollee->last_name . ', ' . $latestEnrollee->first_name);
            $surname = $latestEnrollee->last_name ?? '';
            $givenName = $latestEnrollee->first_name ?? '';
            $middleName = $latestEnrollee->middle_name ?? null;

            // Determine admission semester
            $firstTermCode = $firstEnrollee->term_id ?? null;
            $admissionSemester = $firstTermCode ? Semester::where('term_code', $firstTermCode)->first() : null;

            // Match program
            $programId = $matcher->match($latestEnrollee->degree_program ?? '');

            // Check graduation
            $graduate = Graduate::where('student_number', $studentNumber)->first();
            $isGraduated = !is_null($graduate);

            $existing = Student::withTrashed()->where('student_number', $studentNumber)->first();

            $data = [
                'surname' => $surname,
                'given_name' => $givenName,
                'middle_name' => $middleName,
                'full_name' => $fullName,
                'email' => $latestEnrollee->email ?? null,
                'student_status' => $isGraduated ? 'graduated' : 'active',
            ];

            // Only set these if not already set (don't overwrite admin edits)
            if (!$existing) {
                $data['program_id'] = $programId;
                $data['admission_semester_id'] = $admissionSemester?->id;
                $data['admission_date'] = $admissionSemester?->start_date;
                if ($isGraduated) {
                    $data['graduation_date'] = $graduate->created_at?->toDateString();
                }
            }

            $student = Student::withTrashed()->updateOrCreate(
                ['student_number' => $studentNumber],
                $data
            );

            if ($student->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command->newLine();
        $this->command->info("Done! Created {$created} new students, updated {$updated} existing.");
        $this->command->info("Total students: " . Student::count());
    }
}
