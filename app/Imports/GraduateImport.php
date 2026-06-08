<?php

namespace App\Imports;

use App\Models\Faculty;
use App\Models\Graduate;
use App\Models\GraduateCommitteeMember;
use App\Models\ImportLog;
use App\Models\NormalizationRule;
use App\Models\Program;
use App\Models\ProgramMajor;
use App\Support\NameNormalizer;
use App\Support\SemesterNormalizer;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GraduateImport implements ToModel, WithHeadingRow
{
    protected int $imported = 0;
    protected int $updated = 0;
    protected int $skipped = 0;

    protected ?ImportLog $importLog = null;
    protected ?string $filename = null;
    protected array $facultyLookup = [];
    protected array $recordActions = []; // Per-record tracking

    /** @var array<string, string> Loaded from DB: uppercase from_value => to_value */
    protected array $degreeMap = [];

    /** @var array<string, string> Loaded from DB: lowercase from_value => to_value */
    protected array $majorFieldMap = [];

    public function __construct(?string $filename = null)
    {
        $this->filename = $filename;
        $this->buildFacultyLookup();

        // Load normalization rules from DB
        $degreeRules = NormalizationRule::getMap('degree');
        foreach ($degreeRules as $from => $to) {
            $this->degreeMap[strtoupper(trim($from))] = $to;
        }

        $majorFieldRules = NormalizationRule::getMap('major_field');
        foreach ($majorFieldRules as $from => $to) {
            $this->majorFieldMap[strtolower(trim($from))] = $to;
        }
    }

    private function clean(?string $value): string
    {
        $value = trim($value ?? '');
        return strtolower($value) === 'null' ? '' : $value;
    }

    public function model(array $row)
    {
        $isEmptyRow = true;
        foreach ($row as $value) {
            if ($this->clean((string) $value) !== '') {
                $isEmptyRow = false;
                break;
            }
        }

        if ($isEmptyRow) {
            $this->skipped++;
            return null;
        }

        $name = $this->clean($row['name'] ?? '');

        if (empty($name)) {
            $this->skipped++;
            return null;
        }

        // Normalize name to Title Case
        $name = NameNormalizer::normalizeFullName($name);

        // Normalize semester — keep raw for auditing
        $rawSemester = $this->clean($row['semester_graduated'] ?? $row['semester graduated'] ?? '');
        $normalizedSemester = SemesterNormalizer::normalize($rawSemester);

        // Normalize CSV degree abbreviation (initial normalization only)
        $rawDegree = $this->clean($row['degree'] ?? '');
        $csvDegree = $this->normalizeDegree($rawDegree);

        // Clean the raw major field (title case for readability)
        $rawMajorField = $this->clean($row['major_field'] ?? $row['major field'] ?? '');
        $normalizedMajorField = NameNormalizer::normalize($rawMajorField);

        // Apply major_field normalization rules from DB
        $majorLowerKey = strtolower(trim($normalizedMajorField));
        if (isset($this->majorFieldMap[$majorLowerKey])) {
            $normalizedMajorField = $this->majorFieldMap[$majorLowerKey];
        }

        // Resolve CSV degree + major field → system Program
        $resolved = $this->resolveProgram($csvDegree, $normalizedMajorField);

        $data = [
            'name' => $name,
            'semester_graduated' => $normalizedSemester ?: $rawSemester,
            'semester_raw' => $rawSemester ?: null,
            'country_of_origin' => $this->clean($row['country_of_origin'] ?? $row['country of origin'] ?? ''),
            'degree' => $resolved['degree'],
            'program_name' => $resolved['program_name'],
            'program_id' => $resolved['program_id'],
            'major_field_raw' => $normalizedMajorField ?: null,
            'major' => $resolved['major'],
            // Keep legacy flat columns populated for GS data fidelity
            'chair' => NameNormalizer::normalize($this->clean($row['chair'] ?? '')),
            'co_chair' => NameNormalizer::normalize($this->clean($row['co-chair'] ?? $row['cochair'] ?? $row['co_chair'] ?? '')),
            'member1' => NameNormalizer::normalize($this->clean($row['member1'] ?? '')),
            'member2' => NameNormalizer::normalize($this->clean($row['member2'] ?? '')),
            'member3' => NameNormalizer::normalize($this->clean($row['member3'] ?? '')),
            'member4' => NameNormalizer::normalize($this->clean($row['member4'] ?? '')),
            'member5' => NameNormalizer::normalize($this->clean($row['member5'] ?? '')),
        ];

        $existing = Graduate::where('name', $data['name'])
            ->where('degree', $data['degree'])
            ->where('semester_graduated', $data['semester_graduated'])
            ->first();

        if ($existing) {
            $changes = $this->detectChanges($existing, $data);
            $existing->update($data);
            $this->syncCommitteeMembers($existing, $data);
            $this->updated++;
            $this->recordActions[] = [
                'graduate_id' => $existing->id,
                'action' => 'updated',
                'changes' => !empty($changes) ? $changes : null,
            ];
        } else {
            $graduate = Graduate::create(array_merge($data, ['source' => 'imported']));
            $this->syncCommitteeMembers($graduate, $data);
            $this->imported++;
            $this->recordActions[] = [
                'graduate_id' => $graduate->id,
                'action' => 'imported',
                'changes' => null,
            ];
        }

        return null;
    }

    /**
     * Sync committee members for a graduate record from flat columns.
     */
    private function syncCommitteeMembers(Graduate $graduate, array $data): void
    {
        // Clear existing committee members for this graduate (re-sync on each import)
        $graduate->committeeMembers()->delete();

        $members = [];

        // Chair
        if (!empty($data['chair']) && strtolower($data['chair']) !== 'n/a') {
            $members[] = ['name' => $data['chair'], 'role' => 'Chair'];
        }

        // Co-Chair
        if (!empty($data['co_chair']) && strtolower($data['co_chair']) !== 'n/a') {
            $members[] = ['name' => $data['co_chair'], 'role' => 'Co-Chair'];
        }

        // Members 1-5
        for ($i = 1; $i <= 5; $i++) {
            $field = "member{$i}";
            if (!empty($data[$field]) && strtolower($data[$field]) !== 'n/a') {
                $members[] = ['name' => $data[$field], 'role' => 'Member'];
            }
        }

        foreach ($members as $member) {
            $facultyMatch = $this->findFacultyMatch($member['name']);

            // Infer term from the graduate's semester_graduated
            $semesterId = null;
            if (!empty($data['semester_graduated'])) {
                $semesterId = \App\Models\Semester::where('term_code', $data['semester_graduated'])->value('id');
            }

            GraduateCommitteeMember::create([
                'graduate_id' => $graduate->id,
                'faculty_id' => $facultyMatch?->id,
                'name' => $member['name'],
                'role' => $member['role'],
                'match_type' => $facultyMatch ? 'auto' : null,
                'term_start_id' => $semesterId,
                'term_end_id' => $semesterId,
            ]);
        }
    }

    /**
     * Detect which fields changed on an existing graduate record.
     */
    private function detectChanges(Graduate $existing, array $newData): array
    {
        $changes = [];
        $trackFields = ['name', 'degree', 'program_name', 'major', 'chair', 'co_chair',
                         'member1', 'member2', 'member3', 'member4', 'member5',
                         'country_of_origin', 'semester_graduated', 'program_id'];

        foreach ($trackFields as $field) {
            $oldVal = $existing->$field;
            $newVal = $newData[$field] ?? null;
            if ((string)$oldVal !== (string)$newVal) {
                $changes[$field] = ['from' => $oldVal, 'to' => $newVal];
            }
        }

        return $changes;
    }

    /**
     * Finalize import: create the import log with per-record details.
     */
    public function finalize(): ?ImportLog
    {
        $this->importLog = ImportLog::create([
            'type' => 'graduate',
            'import_type' => 'graduate',
            'filename' => $this->filename ?? 'unknown',
            'rows_imported' => $this->imported,
            'rows_updated' => $this->updated,
            'rows_rejected' => $this->skipped,
            'rows_unchanged' => 0,
            'user_id' => auth()->id(),
        ]);

        // Per-record logging
        if ($this->importLog && !empty($this->recordActions)) {
            $batchData = [];
            $now = now();
            foreach ($this->recordActions as $record) {
                $batchData[] = [
                    'import_log_id' => $this->importLog->id,
                    'graduate_id' => $record['graduate_id'],
                    'action' => $record['action'],
                    'changes' => $record['changes'] ? json_encode($record['changes']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            // Batch insert for performance
            foreach (array_chunk($batchData, 100) as $chunk) {
                DB::table('import_log_graduate')->insert($chunk);
            }
        }

        return $this->importLog;
    }

    /**
     * Build a lookup array for fuzzy faculty name matching.
     * Generates multiple normalized variants for each faculty member.
     */
    private function buildFacultyLookup(): void
    {
        $faculty = Faculty::all();
        foreach ($faculty as $f) {
            $first = $f->first_name;
            $middle = $f->middle_name;
            $last = $f->last_name;

            // Generate all plausible name variants
            $variants = [];

            // "Lastname, Firstname Middlename" (accessor format)
            $variants[] = $f->full_name;

            // "Firstname Middlename Lastname" (full forward)
            $variants[] = trim("$first $middle $last");

            // "Firstname M. Lastname" (middle initial with period)
            if ($middle) {
                $mi = mb_substr($middle, 0, 1);
                $variants[] = "$first $mi. $last";
                $variants[] = "$first $mi $last"; // without period
            }

            // "Firstname Lastname" (no middle)
            $variants[] = "$first $last";

            // "Lastname, Firstname" (comma format)
            $variants[] = "$last, $first";
            if ($middle) {
                $variants[] = "$last, $first $middle";
                $mi = mb_substr($middle, 0, 1);
                $variants[] = "$last, $first $mi.";
                $variants[] = "$last, $first $mi";
            }

            // Handle suffixes stored in last name (e.g., "Dela Cruz")
            // Also handle hyphenated names
            $lastParts = preg_split('/[\s-]+/', $last);
            if (count($lastParts) > 1) {
                // Try with the last part only
                $mainLast = end($lastParts);
                $variants[] = "$first $middle $mainLast";
                $variants[] = "$first $mainLast";

                // Try with different casing/spacing of compound name
                $variants[] = "$first $middle " . implode(' ', $lastParts);
                $variants[] = "$first $middle " . implode('-', $lastParts);
            }

            foreach ($variants as $v) {
                $key = $this->normalizeName($v);
                if ($key && !isset($this->facultyLookup[$key])) {
                    $this->facultyLookup[$key] = $f;
                }
            }
        }
    }

    private function findFacultyMatch(string $name): ?Faculty
    {
        $normalized = $this->normalizeName($name);
        if (empty($normalized)) return null;

        // 1. Direct match
        if (isset($this->facultyLookup[$normalized])) {
            return $this->facultyLookup[$normalized];
        }

        // 2. Remove common suffixes (Jr., Sr., III, etc.)
        $cleaned = preg_replace('/,?\s*(jr\.?|sr\.?|iii|ii|iv|v)\s*$/i', '', $normalized);
        $cleaned = trim($cleaned, ', ');
        if ($cleaned !== $normalized && isset($this->facultyLookup[$cleaned])) {
            return $this->facultyLookup[$cleaned];
        }

        // 3. Token-based matching: extract word tokens and compare
        $inputTokens = $this->extractTokens($normalized);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($this->facultyLookup as $key => $faculty) {
            $keyTokens = $this->extractTokens($key);

            // Count matching tokens (order-independent)
            $matchingTokens = 0;
            $usedKeyTokens = [];
            foreach ($inputTokens as $it) {
                foreach ($keyTokens as $ki => $kt) {
                    if (isset($usedKeyTokens[$ki])) continue;
                    // Exact match or one is a prefix of the other (handles middle initial)
                    if ($it === $kt || str_starts_with($it, $kt) || str_starts_with($kt, $it)) {
                        $matchingTokens++;
                        $usedKeyTokens[$ki] = true;
                        break;
                    }
                }
            }

            $maxTokens = max(count($inputTokens), count($keyTokens));
            if ($maxTokens === 0) continue;

            $score = $matchingTokens / $maxTokens;

            // Require at least 2 matching tokens and >66% match ratio
            if ($matchingTokens >= 2 && $score > $bestScore && $score >= 0.66) {
                $bestScore = $score;
                $bestMatch = $faculty;
            }
        }

        if ($bestMatch && $bestScore >= 0.66) {
            return $bestMatch;
        }

        // 4. Levenshtein / similar_text fallback for typos
        $bestSimilarity = 0;
        $bestSimilarMatch = null;
        foreach ($this->facultyLookup as $key => $faculty) {
            // Only compare names of similar length (avoid false positives)
            if (abs(strlen($normalized) - strlen($key)) > 5) continue;

            similar_text($normalized, $key, $pct);
            if ($pct > $bestSimilarity && $pct >= 85) {
                $bestSimilarity = $pct;
                $bestSimilarMatch = $faculty;
            }
        }

        return $bestSimilarMatch;
    }

    /**
     * Extract meaningful name tokens, stripping all punctuation and particles.
     */
    private function extractTokens(string $normalized): array
    {
        // Remove common particles/connectors that cause mismatch
        $normalized = str_replace(['.', ',', '-'], ' ', $normalized);
        $tokens = preg_split('/\s+/', trim($normalized));

        // Filter out single-char tokens that are just initials and very short noise
        return array_values(array_filter($tokens, fn($t) => strlen($t) >= 1));
    }

    /**
     * Normalize a name string for comparison.
     * Strips punctuation, lowercases, normalizes whitespace, handles accents.
     */
    private function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        // Normalize common accent characters
        $value = str_replace(['ñ', 'á', 'é', 'í', 'ó', 'ú'], ['n', 'a', 'e', 'i', 'o', 'u'], $value);
        // Remove all periods and commas (these cause most mismatches)
        $value = str_replace(['.', ','], '', $value);
        // Collapse multiple spaces
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    /**
     * Resolve a CSV degree + major field combination to a system Program.
     */
    private function resolveProgram(string $csvDegree, string $majorField): array
    {
        $result = [
            'program_id' => null,
            'degree' => $csvDegree,
            'program_name' => null,
            'major' => null,
        ];

        if (empty($csvDegree)) {
            return $result;
        }

        $majorLower = mb_strtolower(trim($majorField));

        // For generic degrees (PhD, MS, MA), the major field tells us WHICH specific program
        if (in_array($csvDegree, ['PhD', 'MS', 'MA'])) {
            $programs = Program::all();
            $bestMatch = null;

            foreach ($programs as $program) {
                $progNameLower = mb_strtolower($program->name);
                $progCode = $program->code;

                $codePrefix = mb_strtolower($csvDegree);
                $codeLower = mb_strtolower($progCode);

                $matchesPrefix = str_starts_with($codeLower, $codePrefix);

                if ($matchesPrefix && !empty($majorLower) && str_contains($progNameLower, $majorLower)) {
                    $bestMatch = $program;
                    break;
                }
            }

            if ($bestMatch) {
                $result['program_id'] = $bestMatch->id;
                $result['degree'] = $bestMatch->code;
                $result['program_name'] = $bestMatch->name;
                if (!empty($majorField)) {
                    $matchedMajor = ProgramMajor::where('program_id', $bestMatch->id)
                        ->whereRaw('LOWER(name) = ?', [$majorLower])
                        ->first();
                    if ($matchedMajor) {
                        $result['major'] = $matchedMajor->name;
                    }
                }
            }

            return $result;
        }

        // For specific degrees (MDMG, MPAf, etc.), map directly by code
        $program = Program::whereRaw('LOWER(code) = ?', [mb_strtolower($csvDegree)])->first();

        if ($program) {
            $result['program_id'] = $program->id;
            $result['degree'] = $program->code;
            $result['program_name'] = $program->name;

            if (!empty($majorField)) {
                $matchedMajor = ProgramMajor::where('program_id', $program->id)
                    ->whereRaw('LOWER(name) = ?', [$majorLower])
                    ->first();
                if ($matchedMajor) {
                    $result['major'] = $matchedMajor->name;
                }
            }
        }

        return $result;
    }

    public function getImportedCount(): int { return $this->imported; }
    public function getUpdatedCount(): int { return $this->updated; }
    public function getSkippedCount(): int { return $this->skipped; }
    public function getImportLog(): ?ImportLog { return $this->importLog; }

    /**
     * Normalize CSV degree abbreviation.
     * Checks DB-driven normalization rules first, falls back to hardcoded mappings.
     */
    private function normalizeDegree(string $degree): string
    {
        if ($degree === '') return '';

        $upper = strtoupper(trim($degree));

        // 1. Check DB-driven rules first (highest priority)
        if (isset($this->degreeMap[$upper])) {
            return $this->degreeMap[$upper];
        }

        // 2. Hardcoded fallback for common abbreviations
        $normalized = match ($upper) {
            'PHD', 'PH.D', 'PH.D.', 'DOCTOR OF PHILOSOPHY' => 'PhD',
            'MS', 'M.S', 'M.S.' => 'MS',
            'MA', 'M.A', 'M.A.' => 'MA',
            'MPA', 'M.P.A' => 'MPA',
            'MPAF', 'M.P.AF' => 'MPAf',
            'MPAEM' => 'MPAEM',
            'MDMG' => 'MDMG',
            'DMG' => 'MDMG',
            'DPA' => 'DPA',
            default => null,
        };

        if ($normalized !== null && $normalized !== $degree) {
            // Auto-store the mapping so it appears in the Normalization Rules page
            NormalizationRule::firstOrCreate(
                ['type' => 'degree', 'from_value' => $degree],
                ['to_value' => $normalized]
            );
            // Cache it for subsequent rows in this import
            $this->degreeMap[$upper] = $normalized;
            return $normalized;
        }

        return $degree;
    }
}
