<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change unique constraint on student_programs from (student_id, program_id)
 * to (student_id, program_id, program_major_id) to support students who
 * switch majors within the same program.
 *
 * The new index is added BEFORE dropping the old one so MySQL can use it
 * to satisfy the FK on student_enrollments.student_program_id → student_programs.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_programs', function (Blueprint $table) {
            // Add the new unique constraint first (MySQL needs an index on student_id
            // for the FK before we can drop the old unique that covers student_id)
            $table->unique(['student_id', 'program_id', 'program_major_id']);
        });

        Schema::table('student_programs', function (Blueprint $table) {
            // Now safe to drop the old unique constraint
            $table->dropUnique(['student_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::table('student_programs', function (Blueprint $table) {
            $table->unique(['student_id', 'program_id']);
        });

        Schema::table('student_programs', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'program_id', 'program_major_id']);
        });
    }
};
