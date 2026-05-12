<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add term_start_id and term_end_id to student_committee_members
        Schema::table('student_committee_members', function (Blueprint $table) {
            $table->foreignId('term_start_id')->nullable()->after('appointed_date')->constrained('semesters')->nullOnDelete();
            $table->foreignId('term_end_id')->nullable()->after('term_start_id')->constrained('semesters')->nullOnDelete();
        });

        // Add term_start_id and term_end_id to academic_output_committee
        Schema::table('academic_output_committee', function (Blueprint $table) {
            $table->foreignId('term_start_id')->nullable()->after('appointed_date')->constrained('semesters')->nullOnDelete();
            $table->foreignId('term_end_id')->nullable()->after('term_start_id')->constrained('semesters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_committee_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('term_start_id');
            $table->dropConstrainedForeignId('term_end_id');
        });

        Schema::table('academic_output_committee', function (Blueprint $table) {
            $table->dropConstrainedForeignId('term_start_id');
            $table->dropConstrainedForeignId('term_end_id');
        });
    }
};
