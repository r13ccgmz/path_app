<?php

namespace Database\Seeders;

use App\Models\Enrollee;
use App\Models\Graduate;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProgram;
use App\Services\ProgramMatcher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Backfill student_programs from existing enrollees data.
 * For each student in the students table:
 *   1. Find all distinct degree_program values from enrollees
 *   2. Create a student_programs record per program
 *   3. Link student_enrollments to the correct student_program
 */
class StudentProgramSeeder extends Seeder
{
    public function run(): void
    {
        $matcher = ProgramMatcher::instance();
        $students = Student::all();

        $this->command->info("Processing {$students->count()} students...");

        foreach ($students as $student) {
            // Get all enrollment records grouped by degree program
            $enrolleeRecords = Enrollee::where('student_number', $student->student_number)
                ->whereNotNull('degree_program')
                ->where('degree_program', '!=', '')
                ->orderBy('term_id')
                ->get();

            $byProgram = $enrolleeRecords->groupBy('degree_program');
            $seenProgramIds = [];

            foreach ($byProgram as $rawDegree => $enrollments) {
                $programId = $matcher->match($rawDegree);
                if (!$programId || in_array($programId, $seenProgramIds)) continue;
                $seenProgramIds[] = $programId;

                $termIds = $enrollments->pluck('term_id')->unique()->sort()->values();
                $firstTermId = $termIds->first();

                // Find admission semester
                $admissionSemester = Semester::where('term_code', $firstTermId)->first();

                // Count residency terms
                $residencyTerms = $enrollments->filter(function ($e) {
                    return str_contains(strtoupper($e->courses_enrolled ?? ''), 'RESID');
                })->pluck('term_id')->unique()->count();

                // Check graduation status
                $graduate = Graduate::where('student_number', $student->student_number)->first();
                $isGraduatedFromThis = false;
                if ($graduate && $graduate->degree) {
                    // Check if graduation degree matches this program
                    $gradProgramId = $matcher->match($graduate->degree);
                    $isGraduatedFromThis = ($gradProgramId === $programId);
                }

                // Create or update student_programs record
                $sp = StudentProgram::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'program_id' => $programId,
                    ],
                    [
                        'raw_degree_name' => $rawDegree,
                        'admission_semester_id' => $admissionSemester?->id,
                        'admission_date' => $admissionSemester?->start_date,
                        'status' => $isGraduatedFromThis ? 'completed' : 'active',
                        'graduation_date' => $isGraduatedFromThis ? $graduate->created_at?->toDateString() : null,
                        'residency_enrolled' => $residencyTerms,
                    ]
                );

                // Link student_enrollments to this student_program
                // Find semesters matching this program's terms
                $semesterIds = Semester::whereIn('term_code', $termIds->toArray())
                    ->pluck('id')
                    ->toArray();

                if (!empty($semesterIds)) {
                    StudentEnrollment::where('student_id', $student->id)
                        ->whereIn('semester_id', $semesterIds)
                        ->whereNull('student_program_id')
                        ->update(['student_program_id' => $sp->id]);
                }

                $this->command->line(
                    "  [{$student->student_number}] {$rawDegree} → program_id:{$programId}" .
                    " | terms:{$termIds->count()} | residency:{$residencyTerms}" .
                    " | status:{$sp->status} | linked enrollments:" .
                    StudentEnrollment::where('student_program_id', $sp->id)->count()
                );
            }
        }

        $totalSP = StudentProgram::count();
        $linkedEnrollments = StudentEnrollment::whereNotNull('student_program_id')->count();
        $totalEnrollments = StudentEnrollment::count();

        $this->command->newLine();
        $this->command->info("Done! Created {$totalSP} student_programs records.");
        $this->command->info("Linked {$linkedEnrollments}/{$totalEnrollments} enrollments to programs.");
    }
}
