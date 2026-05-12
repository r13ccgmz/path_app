<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedBigInteger('course_offering_id')->nullable();
            $table->foreign('course_offering_id')->references('id')->on('course_offerings')->nullOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('grade', 10)->nullable();
            $table->decimal('grade_numeric', 3, 2)->nullable();
            $table->integer('units_earned')->default(0);
            $table->enum('status', [
                'enrolled', 'completed', 'dropped', 'incomplete',
                'withdrawn', 'in-progress',
            ])->default('enrolled');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
