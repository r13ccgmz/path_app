<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('../data/Faculty Information.csv');

        if (!File::exists($csvPath)) {
            $this->command->warn("Faculty CSV not found at: {$csvPath}");
            return;
        }

        $csv = array_map('str_getcsv', file($csvPath));

        // Skip header row
        $header = array_shift($csv);

        $imported = 0;
        $skipped = 0;

        foreach ($csv as $row) {
            if (count($row) < 4 || empty($row[1])) {
                $skipped++;
                continue;
            }

            $nameParts = $this->parseName($row[1]);
            $designation = trim($row[2] ?? '');
            $highestDegree = $this->parseHighestDegree(trim($row[3] ?? ''));

            // Skip if already exists
            $exists = Faculty::where('last_name', $nameParts['last_name'])
                ->where('first_name', $nameParts['first_name'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Generate placeholder email from name
            $emailBase = strtolower(str_replace(' ', '', $nameParts['first_name'])) . '.' . strtolower(str_replace(' ', '', $nameParts['last_name']));
            $email = $emailBase . '@up.edu.ph';

            Faculty::create([
                'last_name' => $nameParts['last_name'],
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'designation' => $designation,
                'highest_degree' => $highestDegree,
                'email' => $email,
                'employment_status' => 'full-time',
                'staff_classification' => 'faculty',
                'faculty_status' => 'active',
            ]);

            $imported++;
        }

        $this->command->info("Faculty import complete: {$imported} imported, {$skipped} skipped.");
    }

    /**
     * Parse "LASTNAME, FIRSTNAME M" format into components.
     */
    private function parseName(string $fullName): array
    {
        $fullName = trim($fullName);

        // Format: "LASTNAME, FIRSTNAME MIDDLEINITIAL"
        $parts = explode(',', $fullName, 2);
        $lastName = trim($parts[0] ?? '');
        $restOfName = trim($parts[1] ?? '');

        // Split first name and middle initial
        $nameParts = preg_split('/\s+/', $restOfName);
        $firstName = '';
        $middleName = null;

        if (count($nameParts) >= 2) {
            // Last token is likely middle initial (e.g. "G" or "G.")
            $lastToken = end($nameParts);
            if (strlen($lastToken) <= 3) {
                $middleName = $lastToken;
                array_pop($nameParts);
            }
            $firstName = implode(' ', $nameParts);
        } else {
            $firstName = $restOfName;
        }

        // Title case
        $lastName = mb_convert_case(mb_strtolower($lastName), MB_CASE_TITLE);
        $firstName = mb_convert_case(mb_strtolower($firstName), MB_CASE_TITLE);

        return [
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_name' => $middleName ?: null,
        ];
    }

    /**
     * Parse the degree string to extract the degree level.
     */
    private function parseHighestDegree(string $degreeString): string
    {
        $lower = strtolower($degreeString);

        if (str_contains($lower, 'phd') || str_contains($lower, 'ph.d') || str_contains($lower, 'doctor')) {
            return 'doctorate';
        }
        if (str_contains($lower, 'master') || str_contains($lower, 'ms ') || str_contains($lower, 'ma ') || str_contains($lower, 'm.s.') || str_contains($lower, 'm.a.')) {
            return 'masters';
        }
        if (str_contains($lower, 'bachelor') || str_contains($lower, 'bs ') || str_contains($lower, 'ba ') || str_contains($lower, 'b.s.') || str_contains($lower, 'b.a.')) {
            return 'bachelors';
        }

        return 'n/a';
    }
}
