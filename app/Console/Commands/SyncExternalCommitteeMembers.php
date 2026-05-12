<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Faculty;
use App\Models\Graduate;

class SyncExternalCommitteeMembers extends Command
{
    protected $signature = 'app:sync-external-members';
    protected $description = 'Scans the graduates table and imports unique external committee members to the faculty table';

    public function handle()
    {
        $this->info("Scanning graduates table for committee members...");

        $graduates = Graduate::all();
        $fields = ['chair', 'co_chair', 'member1', 'member2', 'member3', 'member4', 'member5'];

        // Collect all raw names
        $rawNames = [];
        foreach ($graduates as $grad) {
            foreach ($fields as $field) {
                $name = trim($grad->$field ?? '');
                if (!empty($name)) {
                    $rawNames[] = $name;
                }
            }
        }

        $this->info("Found " . count($rawNames) . " total name entries.");

        // Normalize and deduplicate:
        // 1. Uppercase everything
        // 2. Remove extra spaces
        // 3. Strip common punctuation noise
        // 4. Skip junk entries like "N/A", empty, single chars
        $normalized = [];
        foreach ($rawNames as $raw) {
            $clean = mb_strtoupper(trim($raw));
            $clean = preg_replace('/\s+/', ' ', $clean); // collapse whitespace

            // Skip junk
            if (in_array($clean, ['N/A', 'NA', 'NONE', '-', '']) || strlen($clean) < 3) {
                continue;
            }

            // Use the normalized form as key, keep the first raw form as display
            if (!isset($normalized[$clean])) {
                $normalized[$clean] = $raw;
            }
        }

        $this->info("After deduplication: " . count($normalized) . " unique names.");

        // Now check against existing faculty (both real and external)
        $existingFaculty = Faculty::withTrashed()->get();

        // Build a set of normalized existing names for fast lookup
        $existingNormalized = [];
        foreach ($existingFaculty as $f) {
            // Match by "FIRST LAST" and "LAST, FIRST"
            $fullA = mb_strtoupper(trim($f->first_name . ' ' . $f->last_name));
            $fullB = mb_strtoupper(trim($f->last_name . ', ' . $f->first_name));
            $existingNormalized[$fullA] = true;
            $existingNormalized[$fullB] = true;
            // Also index just the last name for partial matching
        }

        $added = 0;
        $skipped = 0;

        foreach ($normalized as $cleanName => $displayName) {
            // Check exact match against existing faculty names
            if (isset($existingNormalized[$cleanName])) {
                $skipped++;
                continue;
            }

            // Also check if any existing faculty name is contained in this name or vice versa
            $found = false;
            foreach (array_keys($existingNormalized) as $existing) {
                // If the names share >80% similarity, consider it a match
                similar_text($cleanName, $existing, $pct);
                if ($pct >= 85) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $skipped++;
                continue;
            }

            // Parse the name: assume "FIRST MIDDLE LAST" or "FIRST LAST"
            $parts = explode(' ', $cleanName);

            // Handle suffixes like "JR.", "III", "IV", "II"
            $suffixes = ['JR.', 'JR', 'SR.', 'SR', 'II', 'III', 'IV', 'V'];
            $suffix = null;
            if (count($parts) > 2 && in_array(end($parts), $suffixes)) {
                $suffix = array_pop($parts);
            }

            $lastName = array_pop($parts);
            $firstName = implode(' ', $parts);

            if (empty($firstName)) {
                $firstName = $lastName;
            }

            // Title-case for cleaner display
            $firstName = Str::title(mb_strtolower($firstName));
            $lastName = Str::title(mb_strtolower($lastName));

            $faculty = Faculty::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'suffix' => $suffix,
                'designation' => 'External Member',
                'is_external' => true,
                'is_active' => true,
                'faculty_status' => 'active',
            ]);

            // Add to lookup so subsequent iterations don't duplicate
            $existingNormalized[$cleanName] = true;

            $added++;
            $this->line("  + {$faculty->full_name}");
        }

        $this->info("Done! Added {$added} new external members. Skipped {$skipped} (already exist or matched).");
    }
}
