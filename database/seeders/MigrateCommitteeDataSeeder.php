<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Graduate;
use App\Models\GraduateCommitteeMember;
use App\Models\AcademicOutputCommittee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Migrates existing graduate flat-column committee data into
 * the normalized graduate_committee_members table, and backfills
 * faculty_id on academic_output_committee records.
 */
class MigrateCommitteeDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->migrateGraduateCommittees();
        $this->backfillAoCommitteeFacultyIds();
    }

    /**
     * Migrate graduates.chair/co_chair/member1..5 → graduate_committee_members rows.
     */
    private function migrateGraduateCommittees(): void
    {
        $faculty = Faculty::all();
        $facultyLookup = $this->buildFacultyLookup($faculty);

        $graduates = Graduate::all();
        $created = 0;
        $matched = 0;

        foreach ($graduates as $graduate) {
            // Skip if already migrated
            if ($graduate->committeeMembers()->count() > 0) {
                continue;
            }

            $members = [];

            // Chair
            if (!empty($graduate->chair) && strtolower($graduate->chair) !== 'n/a') {
                $members[] = ['name' => $graduate->chair, 'role' => 'Chair'];
            }

            // Co-Chair
            if (!empty($graduate->co_chair) && strtolower($graduate->co_chair) !== 'n/a') {
                $members[] = ['name' => $graduate->co_chair, 'role' => 'Co-Chair'];
            }

            // Members 1-5
            for ($i = 1; $i <= 5; $i++) {
                $field = "member{$i}";
                if (!empty($graduate->$field) && strtolower($graduate->$field) !== 'n/a') {
                    $members[] = ['name' => $graduate->$field, 'role' => 'Member'];
                }
            }

            foreach ($members as $member) {
                $facultyMatch = $this->findFacultyMatch($member['name'], $facultyLookup);

                GraduateCommitteeMember::create([
                    'graduate_id' => $graduate->id,
                    'faculty_id' => $facultyMatch?->id,
                    'name' => $member['name'],
                    'role' => $member['role'],
                    'match_type' => $facultyMatch ? 'auto' : null,
                ]);

                $created++;
                if ($facultyMatch) $matched++;
            }
        }

        $this->command->info("Graduate committees migrated: {$created} rows created, {$matched} auto-matched to faculty.");
    }

    /**
     * Backfill academic_output_committee.faculty_id from name column.
     */
    private function backfillAoCommitteeFacultyIds(): void
    {
        $faculty = Faculty::all();
        $facultyLookup = $this->buildFacultyLookup($faculty);

        $records = AcademicOutputCommittee::whereNull('faculty_id')
            ->whereNotNull('name')
            ->get();

        $matched = 0;

        foreach ($records as $record) {
            $facultyMatch = $this->findFacultyMatch($record->name, $facultyLookup);
            if ($facultyMatch) {
                $record->update(['faculty_id' => $facultyMatch->id]);
                $matched++;
            }
        }

        $this->command->info("AO committee faculty backfill: {$matched}/{$records->count()} records matched.");
    }

    /**
     * Build a lookup array for fuzzy name matching.
     * Maps normalized name variants to Faculty model instances.
     */
    private function buildFacultyLookup($faculty): array
    {
        $lookup = [];

        foreach ($faculty as $f) {
            // Full name: "Lastname, Firstname Middlename"
            $fullName = $this->normalize($f->full_name);
            $lookup[$fullName] = $f;

            // Also index "FIRSTNAME MIDDLEINITIAL. LASTNAME" format (CSV format)
            $csvFormat = $this->normalize(
                trim($f->first_name . ' ' . ($f->middle_name ? mb_substr($f->middle_name, 0, 1) . '.' : '') . ' ' . $f->last_name)
            );
            $lookup[$csvFormat] = $f;

            // Also "FIRSTNAME LASTNAME" (no middle)
            $shortFormat = $this->normalize($f->first_name . ' ' . $f->last_name);
            $lookup[$shortFormat] = $f;

            // Also "LASTNAME, FIRSTNAME" (comma format without middle)
            $commaFormat = $this->normalize($f->last_name . ', ' . $f->first_name);
            $lookup[$commaFormat] = $f;
        }

        return $lookup;
    }

    /**
     * Try to find a faculty match for a given name string.
     */
    private function findFacultyMatch(string $name, array $lookup): ?Faculty
    {
        $normalized = $this->normalize($name);

        // Direct match
        if (isset($lookup[$normalized])) {
            return $lookup[$normalized];
        }

        // Try removing suffixes like Jr., III, IV
        $cleaned = preg_replace('/,?\s*(jr\.?|sr\.?|ii+|iv|v|vi+)\s*$/i', '', $normalized);
        $cleaned = trim($cleaned, ', ');
        if (isset($lookup[$cleaned])) {
            return $lookup[$cleaned];
        }

        // Try partial matching — check if the normalized name contains any faculty name
        foreach ($lookup as $key => $faculty) {
            if (strlen($key) > 5 && (str_contains($normalized, $key) || str_contains($key, $normalized))) {
                return $faculty;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        // Remove extra spaces
        $value = preg_replace('/\s+/', ' ', $value);
        // Remove accent marks for comparison
        $value = str_replace(['ñ'], ['n'], $value);
        return $value;
    }
}
