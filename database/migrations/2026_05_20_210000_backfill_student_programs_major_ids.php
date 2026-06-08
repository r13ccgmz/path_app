<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Services\ProgramMatcher;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $matcher = ProgramMatcher::instance();

        // 1. Backfill StudentProgram
        $studentPrograms = StudentProgram::all();
        foreach ($studentPrograms as $sp) {
            if (!$sp->program_major_id && $sp->raw_degree_name) {
                $majorId = $matcher->matchMajor($sp->program_id, $sp->raw_degree_name);
                if ($majorId) {
                    $sp->program_major_id = $majorId;
                    $sp->save();
                }
            }
        }

        // 2. Backfill Student
        $students = Student::all();
        foreach ($students as $student) {
            if (!$student->program_major_id) {
                $latestSpWithMajor = StudentProgram::where('student_id', $student->id)
                    ->whereNotNull('program_major_id')
                    ->first();
                if ($latestSpWithMajor) {
                    $student->program_major_id = $latestSpWithMajor->program_major_id;
                    $student->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down action needed for data backfills
    }
};
