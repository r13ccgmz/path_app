<?php

namespace App\Support;

class NameNormalizer
{
    /**
     * Words that should remain lowercase when not at the start of a name.
     */
    protected static array $particles = [
        'de', 'dela', 'del', 'delos', 'las', 'los', 'van', 'von', 'di', 'da', 'le', 'la',
    ];

    /**
     * Suffixes that have specific casing rules.
     */
    protected static array $suffixes = [
        'jr' => 'Jr.',
        'jr.' => 'Jr.',
        'sr' => 'Sr.',
        'sr.' => 'Sr.',
        'ii' => 'II',
        'iii' => 'III',
        'iv' => 'IV',
        'v' => 'V',
    ];

    /**
     * Prefixes that trigger camelCase-style capitalization.
     */
    protected static array $camelPrefixes = [
        'mc' => 'Mc',
        'mac' => 'Mac',
    ];

    /**
     * Normalize a name to proper Title Case.
     *
     * Handles:
     * - ALL CAPS → Title Case
     * - Filipino particles (de, dela, del, delos)
     * - Suffixes (Jr., Sr., II, III, IV)
     * - CamelCase prefixes (Mc, Mac)
     * - Special characters (Ñ/ñ)
     * - Hyphenated names
     * - Already-proper names left intact
     */
    public static function normalize(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return $name;
        }

        $name = trim($name);

        // Fix encoding for ñ
        $name = str_replace("\xEF\xBF\xBD", 'Ñ', $name);

        // If the name is already mixed case (not all uppercase or all lowercase), leave it alone
        // Exception: if it's ALL CAPS, always normalize
        if ($name !== mb_strtoupper($name, 'UTF-8') && $name !== mb_strtolower($name, 'UTF-8')) {
            return $name;
        }

        // Split by spaces and process each word
        $words = preg_split('/\s+/', $name);
        $result = [];

        foreach ($words as $index => $word) {
            if ($word === '') continue;

            $lower = mb_strtolower($word, 'UTF-8');

            // Check for suffixes
            $stripped = rtrim($lower, '.,');
            if (isset(static::$suffixes[$stripped]) || isset(static::$suffixes[$lower])) {
                $result[] = static::$suffixes[$stripped] ?? static::$suffixes[$lower];
                continue;
            }

            // Check for particles (lowercase unless first word)
            if ($index > 0 && in_array($stripped, static::$particles, true)) {
                $result[] = $lower;
                continue;
            }

            // Process hyphenated parts separately
            if (str_contains($word, '-')) {
                $parts = explode('-', $word);
                $hyphenated = [];
                foreach ($parts as $part) {
                    $hyphenated[] = static::capitalizeWord($part);
                }
                $result[] = implode('-', $hyphenated);
                continue;
            }

            $result[] = static::capitalizeWord($word);
        }

        return implode(' ', $result);
    }

    /**
     * Normalize a full name in "LAST, FIRST MIDDLE" format.
     */
    public static function normalizeFullName(?string $fullName): ?string
    {
        if ($fullName === null || trim($fullName) === '') {
            return $fullName;
        }

        // Split by comma for "Last, First Middle" format
        if (str_contains($fullName, ',')) {
            $parts = explode(',', $fullName, 2);
            $lastName = static::normalize(trim($parts[0]));
            $rest = isset($parts[1]) ? static::normalize(trim($parts[1])) : '';
            return $rest ? "{$lastName}, {$rest}" : $lastName;
        }

        return static::normalize($fullName);
    }

    /**
     * Capitalize a single word, handling Mc/Mac prefixes and ñ.
     */
    protected static function capitalizeWord(string $word): string
    {
        if ($word === '') return '';

        $lower = mb_strtolower($word, 'UTF-8');

        // Check for Mc/Mac prefixes
        foreach (static::$camelPrefixes as $prefix => $replacement) {
            if (str_starts_with($lower, $prefix) && mb_strlen($lower, 'UTF-8') > mb_strlen($prefix, 'UTF-8')) {
                $rest = mb_substr($lower, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8');
                return $replacement . mb_strtoupper(mb_substr($rest, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($rest, 1, null, 'UTF-8');
            }
        }

        // Standard Title Case
        return mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($lower, 1, null, 'UTF-8');
    }
}
