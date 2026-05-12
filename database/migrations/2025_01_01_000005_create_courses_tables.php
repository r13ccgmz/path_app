<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 50)->unique();
            $table->string('course_name', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cognate_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('program_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('program_major_id')->nullable()->constrained('program_majors')->nullOnDelete();
            $table->foreignId('cognate_field_id')->nullable()->constrained('cognate_fields')->nullOnDelete();
            $table->enum('course_type', [
                'core', 'prescribed', 'major', 'specialization', 'cognate', 'elective',
                'thesis', 'dissertation', 'field_study', 'seminar',
            ])->nullable();
            $table->string('semester_offered', 100)->nullable();
            $table->boolean('applies_to_all_majors')->default(true);
            $table->integer('year_level')->nullable();
            $table->enum('semester_recommended', ['1', '2', '3'])->nullable();
            $table->boolean('is_required')->default(true);
            $table->text('description')->nullable();
            $table->integer('units')->nullable();
            $table->decimal('lecture_hours', 3, 1)->nullable();
            $table->decimal('lab_hours', 3, 1)->nullable();
            $table->string('prerequisite_text', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'course_id', 'program_major_id']);
        });

        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->unsignedBigInteger('prerequisite_course_id');
            $table->foreign('prerequisite_course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'prerequisite_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_prerequisites');
        Schema::dropIfExists('program_courses');
        Schema::dropIfExists('cognate_fields');
        Schema::dropIfExists('courses');
    }
};
