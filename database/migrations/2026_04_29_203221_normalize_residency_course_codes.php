<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize residency course codes in the enrollees table.
     * 
     * Variants found: RESIDNCE ., RESIDNCE     ., RESIDENCY (GRD), RESIDENCY
     * - All "RESIDNCE" variants → "RESIDENCY" 
     * - "RESIDENCY (GRD)" is kept as a separate course type
     * - A backup column preserves original imported values
     */
    public function up(): void
    {
        // Step 1: Add backup column for original values
        if (!Schema::hasColumn('enrollees', 'original_courses_enrolled')) {
            Schema::table('enrollees', function (Blueprint $table) {
                $table->text('original_courses_enrolled')->nullable()->after('courses_enrolled');
            });
        }

        // Step 2: Back up current values (only rows containing RESID)
        DB::table('enrollees')
            ->where('courses_enrolled', 'LIKE', '%RESID%')
            ->whereNull('original_courses_enrolled')
            ->update([
                'original_courses_enrolled' => DB::raw('courses_enrolled'),
            ]);

        // Step 3: Ensure RESIDENCY (GRD) exists as a separate course
        $grdExists = DB::table('courses')->where('course_code', 'RESIDENCY (GRD)')->exists();
        if (!$grdExists) {
            DB::table('courses')->insert([
                'course_code' => 'RESIDENCY (GRD)',
                'course_name' => 'Graduate Residency',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Step 4: Normalize enrollees.courses_enrolled
        // Process each affected row individually to handle mixed entries like "DM 298, RESIDNCE ."
        $affected = DB::table('enrollees')
            ->where('courses_enrolled', 'LIKE', '%RESID%')
            ->get();

        foreach ($affected as $enrollee) {
            $original = $enrollee->courses_enrolled;
            
            // Skip rows that are already clean
            if ($original === 'RESIDENCY' || $original === 'RESIDENCY (GRD)') {
                continue;
            }

            $normalized = $this->normalizeResidencyString($original);
            
            if ($normalized !== $original) {
                DB::table('enrollees')
                    ->where('id', $enrollee->id)
                    ->update(['courses_enrolled' => $normalized]);
            }
        }

        // Step 5: Fix student_enrollments that reference misspelled residency course IDs
        $canonicalId = DB::table('courses')->where('course_code', 'RESIDENCY')->value('id');
        $grdId = DB::table('courses')->where('course_code', 'RESIDENCY (GRD)')->value('id');

        if ($canonicalId) {
            // Find any misspelled residency course entries
            $misspelled = DB::table('courses')
                ->where('course_code', 'LIKE', '%RESID%')
                ->where('course_code', '!=', 'RESIDENCY')
                ->where('course_code', '!=', 'RESIDENCY (GRD)')
                ->pluck('id')
                ->toArray();

            if (!empty($misspelled)) {
                // Migrate student_enrollments to canonical course ID
                DB::table('student_enrollments')
                    ->whereIn('course_id', $misspelled)
                    ->update(['course_id' => $canonicalId]);

                // Migrate program_courses to canonical course ID  
                DB::table('program_courses')
                    ->whereIn('course_id', $misspelled)
                    ->update(['course_id' => $canonicalId]);
            }
        }
    }

    /**
     * Normalize a courses_enrolled string by cleaning residency variants.
     */
    private function normalizeResidencyString(string $input): string
    {
        // Split by comma (with flexible whitespace)
        $parts = preg_split('/\s*,\s*/', $input);
        $normalized = [];

        foreach ($parts as $part) {
            $trimmed = trim($part);
            if (empty($trimmed)) continue;
            
            $upper = strtoupper($trimmed);

            // Check if this part is a residency variant
            if (str_contains($upper, 'RESID')) {
                if (str_contains($upper, 'GRD') || str_contains($upper, '(GRD)')) {
                    $normalized[] = 'RESIDENCY (GRD)';
                } else {
                    $normalized[] = 'RESIDENCY';
                }
            } else {
                // Regular course code — normalize extra whitespace
                $normalized[] = preg_replace('/\s+/', ' ', $trimmed);
            }
        }

        // Some imported rows have courses without comma separators like "CED 298 RESIDNCE ."
        // Check if any single part contains both a course code AND residency
        if (count($normalized) === 1 && count($parts) === 1) {
            $single = $parts[0];
            $upper = strtoupper($single);
            if (str_contains($upper, 'RESID') && preg_match('/^([A-Z]+\s+\d+)\s+RESID/i', trim($single), $matches)) {
                $courseCode = preg_replace('/\s+/', ' ', trim($matches[1]));
                $residency = str_contains($upper, 'GRD') ? 'RESIDENCY (GRD)' : 'RESIDENCY';
                $normalized = [$courseCode, $residency];
            }
        }

        return implode(', ', $normalized);
    }

    public function down(): void
    {
        // Restore original values from backup
        DB::table('enrollees')
            ->whereNotNull('original_courses_enrolled')
            ->update([
                'courses_enrolled' => DB::raw('original_courses_enrolled'),
            ]);

        Schema::table('enrollees', function (Blueprint $table) {
            $table->dropColumn('original_courses_enrolled');
        });
    }
};
