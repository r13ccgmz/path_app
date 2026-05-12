<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $foundingYear = 1998;
        $currentYear = (int) date('Y');

        // Phase 1: Generate academic years from founding year to current year
        for ($year = $foundingYear; $year <= $currentYear; $year++) {
            DB::table('academic_years')->updateOrInsert(
                ['year_start' => $year],
                [
                    'year_end' => $year + 1,
                    'is_current' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Phase 2: Import semesters from CSV
        $csvPath = base_path('../data/Academic Semesters.csv');
        if (file_exists($csvPath)) {
            $this->importSemestersFromCsv($csvPath);
        } else {
            $this->command->warn("Academic Semesters CSV not found: {$csvPath}");
            // Fallback: create semesters for latest AY only
            $this->createDefaultSemesters($currentYear);
        }

        // Phase 3: Mark current AY
        $this->markCurrentAcademicYear();

        $this->command->info('Academic years and semesters seeded successfully.');
    }

    private function importSemestersFromCsv(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle); // Skip header row
        $count = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 5) continue;

            $ayId = (int) $data[0]; // academic_year_id from CSV (sequential)
            $period = $data[1];
            $termCode = trim($data[2]);
            $startDate = !empty(trim($data[3])) ? trim($data[3]) : null;
            $endDate = !empty(trim($data[4])) ? trim($data[4]) : null;

            // Look up the actual academic year by its sequential position
            $ay = DB::table('academic_years')
                ->orderBy('year_start')
                ->offset($ayId - 1)
                ->limit(1)
                ->first();

            if (! $ay) continue;

            DB::table('semesters')->updateOrInsert(
                ['term_code' => $termCode],
                [
                    'academic_year_id' => $ay->id,
                    'semester_period' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_current' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $count++;
        }
        fclose($handle);

        $this->command->info("  Imported {$count} semesters from CSV.");
    }

    private function createDefaultSemesters(int $currentYear): void
    {
        $ay = DB::table('academic_years')->where('year_start', $currentYear - 1)->first();
        if (! $ay) return;

        $yearShort = substr($ay->year_start, -2) . substr($ay->year_end, -2);
        $prefix = ($ay->year_start >= 2000) ? '1' : '0';

        $semesters = [
            ['period' => '1', 'code' => $prefix . substr($ay->year_start, -2) . '1'],
            ['period' => '2', 'code' => $prefix . substr($ay->year_start, -2) . '2'],
            ['period' => '3', 'code' => $prefix . substr($ay->year_start, -2) . '3'],
        ];

        foreach ($semesters as $sem) {
            DB::table('semesters')->updateOrInsert(
                ['term_code' => $sem['code']],
                [
                    'academic_year_id' => $ay->id,
                    'semester_period' => $sem['period'],
                    'is_current' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function markCurrentAcademicYear(): void
    {
        // Determine current AY based on date
        $now = now();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('m');

        // AY starts in August: if month >= 8, AY starts this year; otherwise, last year
        $ayStartYear = $currentMonth >= 8 ? $currentYear : $currentYear - 1;

        DB::table('academic_years')->update(['is_current' => false]);
        DB::table('academic_years')
            ->where('year_start', $ayStartYear)
            ->update(['is_current' => true]);

        // Mark current semester
        DB::table('semesters')->update(['is_current' => false]);
        $currentSemester = DB::table('semesters')
            ->where('start_date', '<=', $now->toDateString())
            ->where('end_date', '>=', $now->toDateString())
            ->first();

        if ($currentSemester) {
            DB::table('semesters')
                ->where('id', $currentSemester->id)
                ->update(['is_current' => true]);
        }
    }
}
