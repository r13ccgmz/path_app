<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename student_programs status 'completed' → 'candidate'
     * to reflect that 100% unit completion makes a student a
     * "Candidate for Graduation", not simply "completed".
     */
    public function up(): void
    {
        DB::table('student_programs')
            ->where('status', 'completed')
            ->update(['status' => 'candidate']);
    }

    public function down(): void
    {
        DB::table('student_programs')
            ->where('status', 'candidate')
            ->update(['status' => 'completed']);
    }
};
