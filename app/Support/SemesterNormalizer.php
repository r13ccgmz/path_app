<?php

namespace App\Support;

use App\Models\Semester;
use Illuminate\Support\Facades\DB;

class SemesterNormalizer
{
    /**
     * Mapping of common abbreviations/names to semester periods.
     * Period values: 1 = 1st Semester, 2 = 2nd Semester, 3 = Midyear
     */
    protected static array $periodAliases = [
        // 1st Semester
        'fs' => '1',
        '1s' => '1',
        '1st sem' => '1',
        '1st semester' => '1',
        'first semester' => '1',
        'first sem' => '1',

        // 2nd Semester
        'ss' => '2',
        '2s' => '2',
        '2nd sem' => '2',
        '2nd semester' => '2',
        'second semester' => '2',
        'second sem' => '2',

        // Midyear
        'my' => '3',
        'summer' => '3',
        'midyear' => '3',
        'mid-year' => '3',
        'summer semester' => '3',
        'summer sem' => '3',
    ];

    /**
     * Try to normalize a raw semester string into a valid term_code.
     *
     * Strategy:
     * 1. If it already matches a term_code in the DB, return as-is
     * 2. Try to extract period + AY info and resolve to a term_code
     * 3. Return original value if no match found
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return $raw;
        }

        $raw = trim($raw);

        // If it's already a numeric term code that exists, return as-is
        if (is_numeric($raw) && Semester::where('term_code', $raw)->exists()) {
            return $raw;
        }

        $lower = strtolower($raw);

        // Try to extract academic year info (e.g., "AY 2023-2024", "2023-2024", "FS2020-2021", "MY2014")
        $year = null;
        if (preg_match('/(\d{4})\s*[-–]\s*(\d{2,4})/', $raw, $yearMatch)) {
            $year = $yearMatch[1];
        } elseif (preg_match('/((?:19|20)\d{2})/', $raw, $yearMatch)) {
            // Relaxed: match any 4-digit year (no word boundary needed for MY2014, etc.)
            $year = $yearMatch[1];
        }

        // Try to determine the semester period
        $period = null;
        // Clean the string for matching: remove year info, AY prefix, punctuation
        $cleaned = preg_replace('/\b(ay|a\.y\.?)\b/i', '', $lower);
        $cleaned = preg_replace('/\d{4}\s*[-–]\s*\d{2,4}/', '', $cleaned);
        $cleaned = preg_replace('/\d{4}/', '', $cleaned); // Also remove standalone years like MY2014 → my
        $cleaned = preg_replace('/[,.]/', '', $cleaned);
        $cleaned = trim($cleaned);

        // Try exact match first
        if (isset(static::$periodAliases[$cleaned])) {
            $period = static::$periodAliases[$cleaned];
        } else {
            // Try partial match
            foreach (static::$periodAliases as $alias => $p) {
                if (str_contains($cleaned, $alias)) {
                    $period = $p;
                    break;
                }
            }
        }

        // If we have both period and year, try to find the matching term_code
        if ($period && $year) {
            // First try year as year_start (for FS/SS with range like FS2020-2021)
            $termCode = Semester::join('academic_years', 'semesters.academic_year_id', '=', 'academic_years.id')
                ->where('academic_years.year_start', $year)
                ->where('semesters.semester_period', $period)
                ->value('semesters.term_code');

            // If not found, try year as year_end (for MY/Summer format like MY2023 = midyear of AY 2022-2023)
            if (!$termCode) {
                $termCode = Semester::join('academic_years', 'semesters.academic_year_id', '=', 'academic_years.id')
                    ->where('academic_years.year_end', $year)
                    ->where('semesters.semester_period', $period)
                    ->value('semesters.term_code');
            }

            if ($termCode) {
                return $termCode;
            }
        }

        // If we only have a period (no year), we can't resolve to a specific term_code.
        // Return the original value — the display layer will handle it.
        return $raw;
    }

    /**
     * Get the period number from a raw semester string.
     * Useful when you only need the period type, not the full term_code.
     *
     * @return string|null '1', '2', or '3', or null if unrecognized
     */
    public static function extractPeriod(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $lower = strtolower(trim($raw));

        // Check if it's a numeric term code — last digit is the period
        if (is_numeric($raw) && strlen($raw) >= 4) {
            $lastDigit = substr($raw, -1);
            if (in_array($lastDigit, ['1', '2', '3'])) {
                return $lastDigit;
            }
        }

        // Clean and match
        $cleaned = preg_replace('/\b(ay|a\.y\.?)\b/i', '', $lower);
        $cleaned = preg_replace('/\d{4}\s*[-–]\s*\d{2,4}/', '', $cleaned);
        $cleaned = preg_replace('/[,.]/', '', $cleaned);
        $cleaned = trim($cleaned);

        if (isset(static::$periodAliases[$cleaned])) {
            return static::$periodAliases[$cleaned];
        }

        foreach (static::$periodAliases as $alias => $period) {
            if (str_contains($cleaned, $alias)) {
                return $period;
            }
        }

        return null;
    }
}
