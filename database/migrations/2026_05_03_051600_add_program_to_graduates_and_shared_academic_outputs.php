<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add program linkage to graduates table
        Schema::table('graduates', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('student_number')->constrained('programs')->nullOnDelete();
        });

        // Create pivot table for shared academic outputs
        Schema::create('academic_output_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_output_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('co_author'); // 'primary_author', 'co_author'
            $table->timestamps();
            $table->unique(['academic_output_id', 'student_id']);
        });

        // Backfill: sync existing academic_outputs.student_id -> pivot table as primary_author
        $outputs = DB::table('academic_outputs')->whereNotNull('student_id')->get(['id', 'student_id']);
        $now = now();
        $inserts = [];
        foreach ($outputs as $ao) {
            $inserts[] = [
                'academic_output_id' => $ao->id,
                'student_id' => $ao->student_id,
                'role' => 'primary_author',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($inserts)) {
            DB::table('academic_output_student')->insert($inserts);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_output_student');

        Schema::table('graduates', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');
        });
    }
};
