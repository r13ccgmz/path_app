<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add semester tracking to academic outputs.
     * Both semester_id (FK) and term_code (display/filter) are stored.
     */
    public function up(): void
    {
        Schema::table('academic_outputs', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('student_id')->constrained('semesters')->nullOnDelete();
            $table->string('term_code', 20)->nullable()->after('semester_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('academic_outputs', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['semester_id', 'term_code']);
        });
    }
};
