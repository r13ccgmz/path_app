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
        Schema::table('enrollees', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_major_id')->nullable()->constrained()->nullOnDelete();
        });

        // Backfill existing enrollee records
        $matcher = \App\Services\ProgramMatcher::instance();
        $enrollees = \App\Models\Enrollee::all();
        foreach ($enrollees as $enrollee) {
            if ($enrollee->degree_program) {
                $progId = $matcher->match($enrollee->degree_program);
                $majorId = $progId ? $matcher->matchMajor($progId, $enrollee->degree_program) : null;
                $enrollee->update([
                    'program_id' => $progId,
                    'program_major_id' => $majorId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollees', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['program_major_id']);
            $table->dropColumn(['program_id', 'program_major_id']);
        });
    }
};
