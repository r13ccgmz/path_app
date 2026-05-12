<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number', 20)->unique();

            // Personal Information
            $table->string('surname', 100);
            $table->string('given_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('full_name', 255)->nullable();
            $table->string('email', 255);
            $table->date('birthdate')->nullable();
            $table->enum('sex', ['male', 'female', 'other'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'widowed', 'separated', 'divorced'])->nullable();
            $table->string('nationality', 100)->default('Filipino');
            $table->string('country_of_origin', 100)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('institution_affiliated', 255)->nullable();

            // Academic Information
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('program_major_id')->nullable()->constrained('program_majors')->nullOnDelete();
            $table->foreignId('admission_semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->date('admission_date')->nullable();
            $table->date('expected_graduation_date')->nullable();

            // Advisory Committee
            $table->foreignId('adviser_id')->nullable()->constrained('faculty')->nullOnDelete();

            // Status
            $table->enum('student_status', [
                'active', 'on-leave', 'graduated', 'dismissed',
                'dropped', 'withdrawn', 'loa-approved',
            ])->default('active');
            $table->date('graduation_date')->nullable();
            $table->unsignedBigInteger('graduation_semester_id')->nullable();
            $table->foreign('graduation_semester_id')->references('id')->on('semesters')->nullOnDelete();

            // Cached fields
            $table->integer('total_units_earned')->default(0);
            $table->decimal('gwa', 4, 3)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty')->cascadeOnDelete();
            $table->enum('role', ['co-adviser', 'chair', 'co-chair', 'panel-member']);
            $table->date('appointed_date')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'faculty_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_committee_members');
        Schema::dropIfExists('students');
    }
};
