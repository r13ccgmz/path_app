<?php

namespace App\Services;

use App\Models\NormalizationRule;
use App\Models\Program;

/**
 * Service to match raw degree program text to normalized Program models.
 * Extracted from StudentHistory for reuse in seeders and other contexts.
 */
class ProgramMatcher
{
    private static ?self $instance = null;
    private $programs;
    private $normalizationMap;

    public function __construct()
    {
        $this->programs = Program::all();
        $this->normalizationMap = NormalizationRule::getMap('program');
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Match a raw degree program string to a Program model ID.
     */
    public function match(?string $degreeProgram): ?int
    {
        if (!$degreeProgram) return null;

        // Apply normalization rules
        $normalized = $degreeProgram;
        foreach ($this->normalizationMap as $from => $to) {
            if (strcasecmp($normalized, $from) === 0) {
                $normalized = $to;
                break;
            }
        }

        $normalizedComparable = $this->normalizeAmpersand($normalized);

        // 1. Exact match on program name
        $match = $this->programs->first(fn ($p) =>
            strcasecmp($this->normalizeAmpersand($p->name), $normalizedComparable) === 0
        );
        if ($match) return $match->id;

        // 2. Program code match
        $match = $this->programs->first(fn ($p) => strcasecmp($p->code, $normalized) === 0);
        if ($match) return $match->id;

        // 3. Keyword-pattern matching
        $lower = strtolower($normalizedComparable);

        $patterns = [
            ['check' => fn($l) => str_contains($l, 'research') && str_contains($l, 'development studies'), 'code' => 'PhD-DVST-R'],
            ['check' => fn($l) => str_contains($l, 'doctor') && str_contains($l, 'development studies') && !str_contains($l, 'research'), 'code' => 'PhD-DVST'],
            ['check' => fn($l) => str_contains($l, 'doctor') && str_contains($l, 'community development'), 'code' => 'PhD-CD'],
            ['check' => fn($l) => str_contains($l, 'doctor') && str_contains($l, 'extension education'), 'code' => 'PhD-EE'],
            ['check' => fn($l) => str_contains($l, 'master of science') && str_contains($l, 'development management') && str_contains($l, 'governance'), 'code' => 'MSDMG'],
            ['check' => fn($l) => str_contains($l, 'master') && !str_contains($l, 'master of science') && str_contains($l, 'development management') && str_contains($l, 'governance'), 'code' => 'MDMG'],
            ['check' => fn($l) => str_contains($l, 'master') && str_contains($l, 'public affairs'), 'code' => 'MPAf'],
            ['check' => fn($l) => str_contains($l, 'master of science') && str_contains($l, 'extension education'), 'code' => 'MS-EE'],
            ['check' => fn($l) => str_contains($l, 'master of science') && str_contains($l, 'community development'), 'code' => 'MS-CD'],
        ];

        foreach ($patterns as $pattern) {
            if (($pattern['check'])($lower)) {
                $match = $this->programs->first(fn ($p) => $p->code === $pattern['code']);
                if ($match) return $match->id;
            }
        }

        // 4. Fuzzy partial match
        $match = $this->programs->first(function ($p) use ($normalizedComparable) {
            $programName = strtolower($this->normalizeAmpersand($p->name));
            $input = strtolower($normalizedComparable);
            return str_contains($programName, $input) || str_contains($input, $programName);
        });

        return $match?->id;
    }

    private function normalizeAmpersand(string $value): string
    {
        return preg_replace('/\s*&\s*/', ' and ', $value);
    }
}
