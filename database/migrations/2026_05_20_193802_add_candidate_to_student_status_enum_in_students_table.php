<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE students MODIFY COLUMN student_status ENUM(
            'active',
            'on-leave',
            'leave-of-absence-approved',
            'absent-without-official-leave',
            'graduated',
            'dismissed',
            'dropped',
            'withdrawn',
            'inactive',
            'candidate'
        ) DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('students')
            ->where('student_status', 'candidate')
            ->update(['student_status' => 'active']);

        DB::statement("ALTER TABLE students MODIFY COLUMN student_status ENUM(
            'active',
            'on-leave',
            'leave-of-absence-approved',
            'absent-without-official-leave',
            'graduated',
            'dismissed',
            'dropped',
            'withdrawn',
            'inactive'
        ) DEFAULT 'active'");
    }
};
