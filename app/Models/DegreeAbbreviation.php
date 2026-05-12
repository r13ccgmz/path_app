<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DegreeAbbreviation extends Model
{
    protected $fillable = [
        'abbreviation',
        'display_name',
    ];

    // Get the display name for an abbreviation, returns the abbreviation itself if not found
    public static function expand(string $abbreviation): string
    {
        $result = static::where('abbreviation', $abbreviation)->first();
        return $result ? $result->display_name : $abbreviation;
    }

    // Build a formatted program name from degree abbreviation + major field using the {major} template
    public static function formatProgram(?string $degree, ?string $majorField): string
    {
        if (empty($degree) && empty($majorField)) {
            return '';
        }

        $record = $degree ? static::where('abbreviation', $degree)->first() : null;
        $template = $record ? $record->display_name : ($degree ?: '');

        if (empty($majorField) || !str_contains($template, '{major}')) {
            // No major field or template doesn't use it — return template as-is
            return str_replace('{major}', '', $template);
        }

        // Title-case the major field and fix common words
        $formattedMajor = mb_convert_case(strtolower($majorField), MB_CASE_TITLE);
        $formattedMajor = str_replace(
            [' In ', ' Of ', ' The ', ' For ', ' With '],
            [' in ', ' of ', ' the ', ' for ', ' with '],
            $formattedMajor
        );
        // Keep "and" lowercase too for consistency with system program names
        $formattedMajor = str_replace(' And ', ' and ', $formattedMajor);

        return str_replace('{major}', $formattedMajor, $template);
    }

    /**
     * Reverse of formatProgram: given a full composite name like
     * "Master in Public Affairs in Education Management",
     * extract the raw major field portion ("Education Management")
     * using DegreeAbbreviation templates.
     *
     * Tries ALL templates with {major} placeholder.
     * Returns null if no extraction possible.
     */
    public static function extractMajorField(?string $degree, ?string $fullName): ?string
    {
        if (empty($fullName)) {
            return null;
        }

        // Try to match degree-specific template first, then all templates
        $templates = static::all();

        foreach ($templates as $tmpl) {
            if (!str_contains($tmpl->display_name, '{major}')) {
                continue;
            }

            // If degree is provided, prioritize matching abbreviation
            if (!empty($degree) && mb_strtolower($tmpl->abbreviation) !== mb_strtolower($degree)) {
                continue; // Skip non-matching abbreviations on first pass
            }

            $parts = explode('{major}', $tmpl->display_name, 2);
            $pattern = '/^' . preg_quote($parts[0], '/') . '(.+)' . preg_quote($parts[1] ?? '', '/') . '$/i';

            if (preg_match($pattern, trim($fullName), $matches)) {
                return trim($matches[1]);
            }
        }

        // If no match with degree filter, try all templates
        if (!empty($degree)) {
            foreach ($templates as $tmpl) {
                if (!str_contains($tmpl->display_name, '{major}')) {
                    continue;
                }

                $parts = explode('{major}', $tmpl->display_name, 2);
                $pattern = '/^' . preg_quote($parts[0], '/') . '(.+)' . preg_quote($parts[1] ?? '', '/') . '$/i';

                if (preg_match($pattern, trim($fullName), $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        return null;
    }
}
