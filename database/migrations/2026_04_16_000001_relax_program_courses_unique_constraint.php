<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relax the unique constraint on program_courses to allow the same
     * course to appear in a program under different course_type values.
     *
     * Old: UNIQUE(program_id, course_id, program_major_id)
     * New: UNIQUE(program_id, course_id, program_major_id, course_type)
     *
     * MySQL requires temporarily disabling FK checks when dropping an
     * index that's referenced as part of a foreign key.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('program_courses', function (Blueprint $table) {
            $table->dropUnique(['program_id', 'course_id', 'program_major_id']);
            $table->unique(
                ['program_id', 'course_id', 'program_major_id', 'course_type'],
                'pc_program_course_major_type_unique'
            );
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('program_courses', function (Blueprint $table) {
            $table->dropUnique('pc_program_course_major_type_unique');
            $table->unique(['program_id', 'course_id', 'program_major_id']);
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
