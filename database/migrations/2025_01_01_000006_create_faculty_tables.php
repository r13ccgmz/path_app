<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('faculty', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique();

            // Primary Information
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 20)->nullable();
            $table->string('email', 255)->unique();

            // Employment & Role Information
            $table->enum('employment_status', ['full-time', 'part-time', 'temporary', 'others']);
            $table->string('designation', 100)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->enum('highest_degree', ['high-school', 'vocational', 'bachelors', 'masters', 'doctorate', 'n/a']);
            $table->enum('staff_classification', ['admin', 'reps', 'faculty', 'others']);

            // Personal Information
            $table->date('birthday');
            $table->enum('sex', ['male', 'female', 'other']);
            $table->date('date_hired_cpaf')->nullable();
            $table->integer('year_graduated')->nullable();
            $table->string('contact_number', 20)->nullable();

            // Postgraduate tracking (OPCR #14)
            $table->boolean('is_pursuing_postgrad')->default(false);
            $table->string('postgrad_program', 255)->nullable();
            $table->enum('postgrad_level', ['masters', 'doctorate'])->nullable();

            $table->enum('account_status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // Add FK constraint from users to faculty now that faculty table exists
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('faculty_id')->references('id')->on('faculty')->nullOnDelete();
        });

        Schema::create('specializations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->timestamps();
        });

        Schema::create('faculty_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->foreignId('specialization_id')->constrained('specializations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['faculty_id', 'specialization_id']);
        });

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculty')->nullOnDelete();
            $table->string('schedule', 255)->nullable();
            $table->integer('max_slots')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'semester_id']);
        });

        Schema::create('faculty_performance_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->enum('rating', ['outstanding', 'very-satisfactory', 'satisfactory', 'unsatisfactory']);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('rated_by')->nullable();
            $table->foreign('rated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['faculty_id', 'academic_year_id']);
        });

        Schema::create('faculty_student_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->enum('rating', ['excellent', 'very-satisfactory', 'satisfactory', 'fair', 'poor']);
            $table->decimal('numerical_score', 4, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['faculty_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_student_evaluations');
        Schema::dropIfExists('faculty_performance_ratings');
        Schema::dropIfExists('course_offerings');
        Schema::dropIfExists('faculty_specializations');
        Schema::dropIfExists('specializations');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
        });
        Schema::dropIfExists('faculty');
    }
};
