<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('opcr_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->date('snapshot_date');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();

            // Student metrics
            $table->integer('total_active_students')->nullable();
            $table->integer('total_active_masters_students')->nullable();
            $table->integer('total_active_doctorate_students')->nullable();
            $table->integer('total_graduated_masters')->nullable();
            $table->integer('total_graduated_doctorate')->nullable();
            $table->decimal('masters_completion_rate', 5, 2)->nullable();
            $table->decimal('doctorate_completion_rate', 5, 2)->nullable();
            $table->decimal('ftes', 8, 2)->nullable();
            $table->decimal('ftes_percentage', 5, 2)->nullable();

            // Faculty metrics
            $table->integer('total_faculty')->nullable();
            $table->integer('total_phd_faculty')->nullable();
            $table->integer('total_masters_faculty')->nullable();
            $table->integer('total_fulltime_faculty')->nullable();
            $table->decimal('faculty_student_ratio', 5, 2)->nullable();
            $table->decimal('ftef', 8, 2)->nullable();
            $table->decimal('ftef_percentage', 5, 2)->nullable();

            // PhD faculty mentoring
            $table->integer('phd_faculty_mentoring_phd_grads')->nullable();
            $table->decimal('phd_faculty_mentoring_phd_grads_pct', 5, 2)->nullable();
            $table->integer('phd_faculty_mentoring_masters_grads')->nullable();
            $table->decimal('phd_faculty_mentoring_masters_grads_pct', 5, 2)->nullable();

            // Degree ratios
            $table->decimal('phd_faculty_ratio', 5, 2)->nullable();
            $table->decimal('masters_faculty_ratio', 5, 2)->nullable();

            // REPS ratios
            $table->integer('total_reps')->nullable();
            $table->integer('reps_with_masters')->nullable();
            $table->integer('reps_with_phd')->nullable();
            $table->decimal('reps_masters_ratio', 5, 2)->nullable();
            $table->decimal('reps_phd_ratio', 5, 2)->nullable();

            // Faculty pursuing postgrad
            $table->integer('faculty_pursuing_postgrad')->nullable();

            // Performance ratings
            $table->integer('employees_rated_outstanding')->nullable();
            $table->decimal('employees_rated_outstanding_pct', 5, 2)->nullable();
            $table->integer('employees_rated_vs_or_better')->nullable();
            $table->decimal('employees_rated_vs_or_better_pct', 5, 2)->nullable();

            // Student evaluation
            $table->integer('faculty_with_excellent_to_vs_eval')->nullable();
            $table->decimal('faculty_with_excellent_to_vs_eval_pct', 5, 2)->nullable();

            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opcr_snapshots');
    }
};
