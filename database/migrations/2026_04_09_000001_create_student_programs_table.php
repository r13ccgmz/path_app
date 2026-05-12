<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('program_major_id')->nullable()->constrained('program_majors')->nullOnDelete();
            $table->string('raw_degree_name')->nullable()->comment('Original degree text from enrollees for audit trail');
            $table->foreignId('admission_semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->date('admission_date')->nullable();
            $table->string('status')->default('active')->comment('active, completed, withdrawn, on_leave');
            $table->date('graduation_date')->nullable();
            $table->decimal('gwa', 5, 4)->nullable()->comment('Cached weighted average for this program');
            $table->integer('total_units_earned')->default(0)->comment('Cached total units earned in this program');
            $table->integer('residency_enrolled')->default(0)->comment('Number of RESIDNCE semesters enrolled');
            $table->timestamps();

            $table->unique(['student_id', 'program_id']);
        });

        // Add student_program_id to student_enrollments
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreignId('student_program_id')->nullable()->after('student_id')
                ->constrained('student_programs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_program_id');
        });

        Schema::dropIfExists('student_programs');
    }
};
