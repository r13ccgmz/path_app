<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Add adviser term tracking
            $table->foreignId('adviser_term_start_id')->nullable()->after('adviser_appointed_date')
                ->constrained('semesters')->nullOnDelete();
            $table->foreignId('adviser_term_end_id')->nullable()->after('adviser_term_start_id')
                ->constrained('semesters')->nullOnDelete();

            // Drop temporary adviser columns (same as registration adviser)
            $table->dropConstrainedForeignId('temporary_adviser_id');
            $table->dropColumn('temporary_adviser_appointed_date');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adviser_term_start_id');
            $table->dropConstrainedForeignId('adviser_term_end_id');

            $table->foreignId('temporary_adviser_id')->nullable()
                ->constrained('faculties')->nullOnDelete();
            $table->date('temporary_adviser_appointed_date')->nullable();
        });
    }
};
