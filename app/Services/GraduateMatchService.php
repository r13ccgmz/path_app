<?php

namespace App\Services;

use App\Models\Graduate;
use App\Models\Enrollee;

class GraduateMatchService
{
    // Scan all unmatched graduates and return proposed matches without saving
    public static function scanAll(int $threshold = 80): array
    {
        $unmatched = Graduate::where(function ($q) {
            $q->whereNull('student_number')->orWhere('student_number', '');
        })->get();

        $proposals = [];

        foreach ($unmatched as $graduate) {
            $candidates = static::findCandidates($graduate, 1, $threshold);
            if (!empty($candidates)) {
                $best = $candidates[0];
                $proposals[] = [
                    'graduate_id' => $graduate->id,
                    'graduate_name' => $graduate->name,
                    'graduate_program' => trim(($graduate->degree ?? '') . ' ' . ($graduate->program_name ?? '')),
                    'enrollee_name' => $best['full_name'],
                    'enrollee_program' => $best['degree_program'],
                    'student_number' => $best['student_number'],
                    'similarity' => $best['similarity'],
                ];
            }
        }

        // Sort by similarity descending
        usort($proposals, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $proposals;
    }

    // Commit selected proposals — accepts array of graduate_id => student_number
    public static function commitMatches(array $matches): int
    {
        $count = 0;
        foreach ($matches as $graduateId => $studentNumber) {
            $graduate = Graduate::find($graduateId);
            if ($graduate) {
                $graduate->update([
                    'student_number' => $studentNumber,
                    'match_type' => 'auto',
                ]);
                $count++;
            }
        }
        return $count;
    }

    // Find enrollee candidates for a graduate, sorted by similarity descending
    public static function findCandidates(Graduate $graduate, int $limit = 3, int $minSimilarity = 70): array
    {
        $graduateName = strtolower(trim($graduate->name));
        $nameParts = array_filter(preg_split('/[\s,]+/', $graduateName));

        if (empty($nameParts)) {
            return [];
        }

        // Broad search: enrollee must match ANY name part in first, middle, or last
        $query = Enrollee::query();
        $query->where(function ($outer) use ($nameParts) {
            foreach ($nameParts as $part) {
                $outer->orWhere(function ($q) use ($part) {
                    $q->whereRaw('LOWER(first_name) LIKE ?', ["%{$part}%"])
                      ->orWhereRaw('LOWER(middle_name) LIKE ?', ["%{$part}%"])
                      ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$part}%"]);
                });
            }
        });

        $enrollees = $query->orderBy('term_id', 'desc')->limit(200)->get();

        $seen = [];
        $candidates = [];
        foreach ($enrollees as $enrollee) {
            if (isset($seen[$enrollee->student_number])) {
                continue;
            }
            $seen[$enrollee->student_number] = true;

            $fullName = trim($enrollee->first_name . ' ' . ($enrollee->middle_name ?? '') . ' ' . $enrollee->last_name);

            // Compare graduate name vs first+middle+last
            $enrolleeFullLower = strtolower(trim($enrollee->first_name . ' ' . ($enrollee->middle_name ?? '') . ' ' . $enrollee->last_name));
            similar_text($graduateName, $enrolleeFullLower, $simFull);

            // Compare graduate name vs first+last only
            $enrolleeFirstLast = strtolower(trim($enrollee->first_name . ' ' . $enrollee->last_name));
            similar_text($graduateName, $enrolleeFirstLast, $simShort);

            // Compare graduate name vs first+middle only (handles married name changes)
            $enrolleeFirstMiddle = strtolower(trim($enrollee->first_name . ' ' . ($enrollee->middle_name ?? '')));
            similar_text($graduateName, $enrolleeFirstMiddle, $simFirstMiddle);

            $similarity = max($simFull, $simShort, $simFirstMiddle);

            $candidates[] = [
                'student_number' => $enrollee->student_number,
                'full_name' => $fullName,
                'degree_program' => $enrollee->degree_program ?? '—',
                'term_id' => $enrollee->term_id,
                'similarity' => round($similarity),
            ];
        }

        usort($candidates, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice(array_filter($candidates, fn ($c) => $c['similarity'] >= $minSimilarity), 0, $limit);
    }
}
