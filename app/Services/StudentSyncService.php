<?php

namespace App\Services;

use App\Models\Enrollee;
use App\Models\Graduate;
use App\Models\Student;
use App\Models\StudentProgram;

/**
 * Service to create Student records from Enrollee data.
 *
 * Consolidates the logic previously duplicated across
 * HasStudentSearch::ensureStudentRecord() and
 * PotentialGraduates::syncAllStudentData().
 */
class StudentSyncService
{
    /**
     * Create Student records for any enrollee student numbers
     * that don't yet have a corresponding row in the students table.
     * Also creates StudentProgram records so students appear with
     * their program in the List of Students table.
     *
     * @param  array<string>|null  $studentNumbers  Limit to these student numbers.
     *                                               If null, syncs ALL enrollee student numbers.
     * @return int  Number of Student records created.
     */
    public static function syncStudentsFromEnrollees(?array $studentNumbers = null): int
    {
        $matcher = ProgramMatcher::instance();
        $created = 0;

        // Determine which student numbers to check
        $query = Enrollee::query()
            ->select('student_number')
            ->distinct()
            ->whereNotNull('student_number')
            ->where('student_number', '!=', '')
            ->where('student_number', 'NOT LIKE', 'TEMP-%');

        if ($studentNumbers !== null) {
            $query->whereIn('student_number', $studentNumbers);
        }

        $enrolleeStudentNumbers = $query->pluck('student_number')->toArray();

        if (empty($enrolleeStudentNumbers)) {
            return 0;
        }

        // Filter out those that already exist in the students table (including soft-deleted)
        $existingStudentNumbers = Student::withTrashed()
            ->whereIn('student_number', $enrolleeStudentNumbers)
            ->pluck('student_number')
            ->toArray();

        $missingStudentNumbers = array_diff($enrolleeStudentNumbers, $existingStudentNumbers);

        foreach ($missingStudentNumbers as $studentNumber) {
            $latest = Enrollee::where('student_number', $studentNumber)
                ->orderByDesc('term_id')
                ->first();

            if (!$latest) {
                continue;
            }

            // Fix encoding for names with special characters (e.g. ñ)
            $lastName = self::fixEncoding($latest->last_name);
            $firstName = self::fixEncoding($latest->first_name);
            $middleName = self::fixEncoding($latest->middle_name);

            $fullName = Student::buildFullName($lastName, $firstName, $middleName);

            // Sum total units across all terms
            $totalUnits = (int) Enrollee::where('student_number', $studentNumber)->sum('total_units');

            // Match program via ProgramMatcher
            $programId = $matcher->match($latest->degree_program);
            $programMajorId = $programId ? $matcher->matchMajor($programId, $latest->degree_program) : null;

            // Check graduate status
            $graduate = Graduate::where('student_number', $studentNumber)->first();
            $status = $graduate ? 'graduated' : 'active';

            try {
                $student = Student::create([
                    'student_number' => $studentNumber,
                    'surname' => $lastName,
                    'given_name' => $firstName,
                    'middle_name' => $middleName,
                    'full_name' => $fullName,
                    'email' => $latest->email ?? "{$studentNumber}@up.edu.ph",
                    'birthdate' => $latest->birthdate,
                    'sex' => self::normalizeSex($latest->sex),
                    'marital_status' => self::normalizeMaritalStatus($latest->marital_status),
                    'nationality' => $latest->nationality ?: 'Filipino',
                    'country_of_origin' => $graduate?->country_of_origin,
                    'program_id' => $programId,
                    'program_major_id' => $programMajorId,
                    'admission_semester_id' => null,
                    'admission_date' => null,
                    'student_status' => $status,
                    'total_units_earned' => $totalUnits,
                ]);

                // Create StudentProgram records from all distinct enrollee programs
                self::syncStudentPrograms($student, $matcher);

                $created++;
            } catch (\Illuminate\Database\QueryException $e) {
                // Skip duplicates that may arise from race conditions
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    continue;
                }
                throw $e;
            }
        }

        return $created;
    }

    /**
     * Create StudentProgram records for a newly created student
     * by inspecting their enrollee history for distinct degree programs.
     */
    private static function syncStudentPrograms(Student $student, ProgramMatcher $matcher): void
    {
        $enrolleeRecords = Enrollee::where('student_number', $student->student_number)
            ->whereNotNull('degree_program')
            ->where('degree_program', '!=', '')
            ->orderBy('term_id')
            ->get();

        $byProgram = $enrolleeRecords->groupBy('degree_program');
        $seenPairs = [];

        foreach ($byProgram as $rawDegree => $enrollments) {
            $programId = $matcher->match($rawDegree);
            if (!$programId) continue;

            $programMajorId = $matcher->matchMajor($programId, $rawDegree);
            $pairKey = $programId . ':' . ($programMajorId ?? 'null');

            if (in_array($pairKey, $seenPairs)) {
                continue;
            }
            $seenPairs[] = $pairKey;

            // Count residency semesters
            $residencyTerms = $enrollments->filter(function ($e) {
                return str_contains(strtoupper($e->courses_enrolled ?? ''), 'RESID');
            })->pluck('term_id')->unique()->count();

            // Check graduate status for this specific program
            $graduate = Graduate::where('student_number', $student->student_number)->first();
            $programStatus = ($graduate && $student->student_status === 'graduated') ? 'graduated' : 'active';

            try {
                StudentProgram::create([
                    'student_id' => $student->id,
                    'program_id' => $programId,
                    'program_major_id' => $programMajorId,
                    'raw_degree_name' => $rawDegree,
                    'admission_semester_id' => null,
                    'admission_date' => null,
                    'status' => $programStatus,
                    'residency_enrolled' => $residencyTerms,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Skip duplicates (unique constraint on student_id + program_id + program_major_id)
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Fix encoding issues (mojibake) for names containing special characters like ñ.
     */
    private static function fixEncoding(?string $value): ?string
    {
        if (!$value) return null;

        // Replace UTF-8 replacement character with Ñ (common for Filipino names)
        $value = str_replace("\xEF\xBF\xBD", 'Ñ', $value);

        // Try to convert from Latin-1 to UTF-8 if it appears broken
        if (!mb_check_encoding($value, 'UTF-8')) {
            $converted = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
            if ($converted) {
                $value = $converted;
            }
        }

        return $value;
    }

    private static function normalizeSex(?string $sex): ?string
    {
        if (!$sex) return null;
        $lower = strtolower(trim($sex));
        return match (true) {
            in_array($lower, ['m', 'male']) => 'male',
            in_array($lower, ['f', 'female']) => 'female',
            default => 'other',
        };
    }

    private static function normalizeMaritalStatus(?string $status): ?string
    {
        if (!$status) return null;
        $lower = strtolower(trim($status));
        return match (true) {
            in_array($lower, ['s', 'single']) => 'single',
            in_array($lower, ['m', 'married']) => 'married',
            in_array($lower, ['w', 'widowed']) => 'widowed',
            str_starts_with($lower, 'sep') => 'separated',
            str_starts_with($lower, 'div') => 'divorced',
            default => null,
        };
    }
}
