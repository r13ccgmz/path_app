<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing primary advisers to committee_members
        $studentsWithAdvisers = \Illuminate\Support\Facades\DB::table('students')->whereNotNull('adviser_id')->get();
        
        foreach ($studentsWithAdvisers as $student) {
            \Illuminate\Support\Facades\DB::table('student_committee_members')->insert([
                'student_id' => $student->id,
                'faculty_id' => $student->adviser_id,
                'role' => 'Adviser',
                'appointed_date' => $student->adviser_appointed_date ?? null,
                'term_start_id' => $student->adviser_term_start_id ?? null,
                'term_end_id' => $student->adviser_term_end_id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adviser_id');
            $table->dropColumn('adviser_appointed_date');
            $table->dropConstrainedForeignId('adviser_term_start_id');
            $table->dropConstrainedForeignId('adviser_term_end_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('adviser_id')->nullable()->after('nationality')->constrained('faculty')->nullOnDelete();
            $table->date('adviser_appointed_date')->nullable()->after('adviser_id');
            $table->foreignId('adviser_term_start_id')->nullable()->after('adviser_appointed_date')->constrained('semesters')->nullOnDelete();
            $table->foreignId('adviser_term_end_id')->nullable()->after('adviser_term_start_id')->constrained('semesters')->nullOnDelete();
        });
    }
};
