<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Enrollees ──────────────────────────────────────
        Schema::create('enrollees', function (Blueprint $table) {
            $table->id();
            $table->string('term_id');
            $table->string('campus_id')->nullable();
            $table->string('student_number');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('degree_program')->nullable();
            $table->text('courses_enrolled')->nullable();
            $table->integer('total_units')->nullable();
            $table->string('sex')->nullable();
            $table->string('marital_status')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('nationality')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            $table->unique(['term_id', 'student_number']);
            $table->index('student_number');
            $table->index('term_id');
        });

        // ── Enrollment Courses (lightweight course codes for enrollees) ──
        Schema::create('enrollment_courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code')->unique();
            $table->timestamps();
        });

        // ── Enrollment Course ↔ Enrollee Pivot ──
        Schema::create('enrollment_course_enrollee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_course_id')->constrained()->cascadeOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['enrollee_id', 'enrollment_course_id'], 'ecr_enrollee_course_unique');
        });

        // ── Import Logs ──────────────────────────────────
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->integer('rows_imported')->default(0);
            $table->integer('rows_updated')->default(0);
            $table->integer('rows_rejected')->default(0);
            $table->integer('rows_unchanged')->default(0);
            $table->json('errors')->nullable();
            $table->json('normalized_programs')->nullable();
            $table->string('results_file')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // ── Import Log ↔ Enrollee Pivot ──
        Schema::create('import_log_enrollee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollee_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // 'imported' or 'updated'
            $table->timestamps();

            $table->index(['import_log_id', 'action']);
        });

        // ── Normalization Rules ──────────────────────────
        Schema::create('normalization_rules', function (Blueprint $table) {
            $table->id();
            $table->string('type');       // 'program' or 'course_code'
            $table->string('from_value');
            $table->string('to_value');
            $table->timestamps();

            $table->unique(['type', 'from_value']);
        });

        // Seed normalization rules
        $now = now();
        DB::table('normalization_rules')->insert([
            ['type' => 'program', 'from_value' => 'DR OF PHILO IN COMMUNITY DEV', 'to_value' => 'Doctor of Philosophy in Community Development', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'DR OF PHILO IN DEV STUDIES', 'to_value' => 'Doctor of Philosophy in Development Studies', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'DR OF PHILO IN EXTENSION EDUC', 'to_value' => 'Doctor of Philosophy in Extension Education', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'M DEVELOPMENT MGT & GOVERNANCE', 'to_value' => 'Master in Development Management & Governance', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'MPAF AGRARIAN&RURBAN DEV STUDS', 'to_value' => 'Master in Public Affairs Agrarian & Rurban Development Studies', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'MPAF IN EDUCATION MANAGEMENT', 'to_value' => 'Master in Public Affairs in Education Management', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'MPAF IN STRATEGIC PLAN&PUB POL', 'to_value' => 'Master in Public Affairs in Strategic Planning & Public Policy', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'MS DEVELOPMENT MGT&GOVERNANCE', 'to_value' => 'Master of Science in Development Management and Governance', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'MS IN EXTENSION EDUCATION', 'to_value' => 'Master of Science in Extension Education', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'program', 'from_value' => 'MS IN COMMUNITY DEVELOPMENT', 'to_value' => 'Master of Science in Community Development', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'course_code', 'from_value' => 'RESIDNCE', 'to_value' => 'RESIDENCY', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ── Graduates ────────────────────────────────────
        Schema::create('graduates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('student_number')->nullable();
            $table->string('match_type')->nullable(); // 'auto' or 'manual'
            $table->string('semester_graduated')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->string('degree')->nullable();
            $table->string('major_field')->nullable();
            $table->string('chair')->nullable();
            $table->string('co_chair')->nullable();
            $table->string('member1')->nullable();
            $table->string('member2')->nullable();
            $table->string('member3')->nullable();
            $table->string('member4')->nullable();
            $table->string('member5')->nullable();
            $table->timestamps();

            $table->index('student_number');
            $table->index('name');
            $table->index('degree');
        });

        // ── Degree Abbreviations ─────────────────────────
        Schema::create('degree_abbreviations', function (Blueprint $table) {
            $table->id();
            $table->string('abbreviation')->unique();
            $table->string('display_name');
            $table->timestamps();
        });

        DB::table('degree_abbreviations')->insert([
            ['abbreviation' => 'MPAf', 'display_name' => 'Master in Public Affairs in {major}', 'created_at' => $now, 'updated_at' => $now],
            ['abbreviation' => 'MS', 'display_name' => 'Master of Science in {major}', 'created_at' => $now, 'updated_at' => $now],
            ['abbreviation' => 'PHD', 'display_name' => 'Doctor of Philosophy in {major}', 'created_at' => $now, 'updated_at' => $now],
            ['abbreviation' => 'MDMG', 'display_name' => 'Master in Development Management and Governance', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('degree_abbreviations');
        Schema::dropIfExists('graduates');
        Schema::dropIfExists('import_log_enrollee');
        Schema::dropIfExists('import_logs');
        Schema::dropIfExists('normalization_rules');
        Schema::dropIfExists('enrollment_course_enrollee');
        Schema::dropIfExists('enrollment_courses');
        Schema::dropIfExists('enrollees');
    }
};
