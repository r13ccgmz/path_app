<?php

namespace App\Imports;

use App\Models\EnrollmentCourse;
use App\Models\Enrollee;
use App\Support\NameNormalizer;
use App\Models\NormalizationRule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Carbon;

class EnrolleeImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    protected array $processedResults = [];
    public function getProcessedResults(): array
    {
        return $this->processedResults;
    }
        use SkipsFailures;

    protected int $imported = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected int $skippedBlank = 0;

    /** @var array<int, array{id: int, action: string}> */
    protected array $affectedRows = [];

    /** @var array<int, array{student_number: string, term_id: string, original: string, normalized: string}> */
    protected array $normalizedRows = [];

    /** @var array<string, string> Loaded from DB: lowercase from_value => to_value */
    protected array $programMap = [];

    /** @var array<string, string> Loaded from DB: from_value => to_value */
    protected array $courseCodeMap = [];

    /**
     * Human-readable labels for field names used in the Changes column.
     */
    protected const FIELD_LABELS = [
        'last_name' => 'Last Name',
        'first_name' => 'First Name',
        'middle_name' => 'Middle Name',
        'degree_program' => 'Program',
        'courses_enrolled' => 'Courses',
        'total_units' => 'Units',
        'sex' => 'Sex',
        'birthdate' => 'Birthdate',
        'nationality' => 'Nationality',
        'email' => 'Email',
        'marital_status' => 'Marital Status',
    ];

    public function __construct()
    {
        // Load normalization rules from DB (program keys stored uppercase, compared case-insensitively)
        $programRules = NormalizationRule::getMap('program');
        foreach ($programRules as $from => $to) {
            $this->programMap[strtolower($from)] = $to;
        }

        $this->courseCodeMap = NormalizationRule::getMap('course_code');
    }

    /**
     * Clean a value: convert the literal string "null" to empty string.
     * Also fixes common encoding issues (replacement character -> Ñ).
     */
    private function clean(?string $value): string
    {
        $value = trim($value ?? '');
        $value = str_replace("\xEF\xBF\xBD", 'Ñ', $value); // Fix broken Ñ
        return strtolower($value) === 'null' ? '' : $value;
    }

    public function model(array $row)
    {
        $remark = '';
        $changeParts = [];

        $isEmptyRow = true;
        foreach ($row as $key => $value) {
            if ($this->clean((string) $value) !== '') {
                $isEmptyRow = false;
                break;
            }
        }

        if ($isEmptyRow) {
            $this->skippedBlank++;
            return null;
        }

        $termId = $this->clean($row['term_code'] ?? $row['term code'] ?? $row['termcode'] ?? '');
        $studentNumber = $this->clean($row['student_number'] ?? $row['student number'] ?? $row['studentnumber'] ?? '');

        // Skip row if both term and student number are missing
        if (empty($termId) && empty($studentNumber)) {
            $this->skippedBlank++;
            $row['Remarks'] = 'Skipped';
            $row['Changes'] = 'Missing both term code and student number';
            $this->processedResults[] = $row;
            return null;
        }

        // Parse birthdate safely
        $birthdate = null;
        $rawBirthdate = $this->clean($row['birthdate'] ?? '');
        if (!empty($rawBirthdate)) {
            try {
                if (is_numeric($rawBirthdate)) {
                    $birthdate = Carbon::createFromTimestamp(
                        ($rawBirthdate - 25569) * 86400
                    )->format('Y-m-d');
                } else {
                    $birthdate = Carbon::parse($rawBirthdate)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $birthdate = null;
            }
        }

        // Parse total_units
        $rawUnits = $this->clean($row['total_units'] ?? $row['totalunits'] ?? $row['total units'] ?? '');
        if ($rawUnits !== '') {
            $totalUnits = (int) $rawUnits;
        } else {
            $totalUnits = null;
        }

        $rawProgram = $this->clean($row['degree_program'] ?? $row['degree program'] ?? $row['degreeprogram'] ?? '');
        $normalizedProgram = $this->normalizeProgram($rawProgram, $studentNumber, $termId);
        
        if ($rawProgram !== '' && $rawProgram !== $normalizedProgram) {
            $changeParts[] = "Program normalized: '{$rawProgram}' → '{$normalizedProgram}'";
        }

        // Clean names first, then normalize to Title Case
        $rawLastName = $this->clean($row['lastfamily_name'] ?? $row['lastfamily name'] ?? $row['last_name'] ?? $row['last name'] ?? '');
        $rawFirstName = $this->clean($row['first_name'] ?? $row['firstname'] ?? $row['first name'] ?? '');
        $rawMiddleName = $this->clean($row['middle_name'] ?? $row['middlename'] ?? $row['middle name'] ?? '');

        $normalizedLastName = NameNormalizer::normalize($rawLastName);
        $normalizedFirstName = NameNormalizer::normalize($rawFirstName);
        $normalizedMiddleName = NameNormalizer::normalize($rawMiddleName);

        if ($rawLastName !== '' && $rawLastName !== $normalizedLastName) {
            $changeParts[] = "Name normalized: '{$rawLastName}, {$rawFirstName}' → '{$normalizedLastName}, {$normalizedFirstName}'";
        }

        $data = [
            'last_name' => $normalizedLastName,
            'first_name' => $normalizedFirstName,
            'middle_name' => $normalizedMiddleName,
            'degree_program' => $normalizedProgram,
            'courses_enrolled' => $this->clean($row['courses_enrolled'] ?? $row['coursesenrolled'] ?? $row['courses enrolled'] ?? ''),
            'total_units' => $totalUnits,
            'sex' => $this->clean($row['sex'] ?? ''),
            'birthdate' => $birthdate,
            'nationality' => $this->clean($row['nationality'] ?? ''),
            'email' => $this->clean($row['email'] ?? ''),
            'marital_status' => $this->clean($row['marital_status'] ?? $row['maritalstatus'] ?? $row['marital status'] ?? ''),
        ];

        if (empty($studentNumber)) {
            $existingValid = Enrollee::where('last_name', $data['last_name'])
                ->where('first_name', $data['first_name'])
                ->whereNotNull('student_number')
                ->where('student_number', '!=', '')
                ->first();

            if ($existingValid) {
                $studentNumber = $existingValid->student_number;
                $changeParts[] = "Student Number recovered from existing records";
            } else {
                $slug = \Illuminate\Support\Str::slug($data['last_name'] . '-' . $data['first_name'], '');
                $studentNumber = 'TEMP-' . strtoupper(substr($slug, 0, 15));
                $changeParts[] = "Temporary Student Number generated";
            }
        }

        // Upsert: unique on (term_id, student_number)
        $existing = Enrollee::where('term_id', $termId)
            ->where('student_number', $studentNumber)
            ->first();

        if ($existing) {
            // Protect personal info: do not overwrite existing data with empty values
            $protectedFields = ['email', 'sex', 'birthdate', 'nationality', 'marital_status'];
            foreach ($protectedFields as $field) {
                if (empty($data[$field])) {
                    unset($data[$field]);
                }
            }

            // Diff: compare new data against existing record
            $fieldChanges = [];
            foreach ($data as $field => $newValue) {
                $oldValue = $existing->{$field};

                // Normalize values for comparison based on type
                if ($field === 'birthdate') {
                    $oldStr = $oldValue instanceof \Carbon\Carbon ? $oldValue->format('Y-m-d') : (string) ($oldValue ?? '');
                    $newStr = (string) ($newValue ?? '');
                } elseif ($field === 'total_units') {
                    $oldStr = (string) (int) ($oldValue ?? 0);
                    $newStr = (string) (int) ($newValue ?? 0);
                } else {
                    $oldStr = trim((string) ($oldValue ?? ''));
                    $newStr = trim((string) ($newValue ?? ''));
                }

                if ($oldStr !== $newStr) {
                    $label = self::FIELD_LABELS[$field] ?? $field;
                    $oldDisplay = $oldStr === '' ? '(blank)' : $oldStr;
                    $newDisplay = $newStr === '' ? '(blank)' : $newStr;
                    $fieldChanges[] = "{$label}: {$oldDisplay} → {$newDisplay}";
                }
            }

            if (count($fieldChanges) > 0) {
                $existing->update($data);
                $this->updated++;
                $this->affectedRows[] = ['id' => $existing->id, 'action' => 'updated'];
                $remark = 'Updated';
                $changeParts = array_merge($changeParts, $fieldChanges);
            } else {
                $this->skipped++;
                $remark = 'Unchanged';
            }
        } else {
            $this->imported++;
            $enrollee = Enrollee::create(array_merge([
                'term_id' => $termId,
                'student_number' => $studentNumber,
            ], $data));
            $this->affectedRows[] = ['id' => $enrollee->id, 'action' => 'imported'];
            $remark = 'Imported';
        }

        // Sync courses to pivot table
        $targetEnrollee = $existing ?? ($enrollee ?? null);
        if ($targetEnrollee && !empty($data['courses_enrolled'])) {
            $this->syncCourses($targetEnrollee, $data['courses_enrolled']);
        }

        $row['Remarks'] = $remark;
        $row['Changes'] = implode(' | ', $changeParts);
        $this->processedResults[] = $row;

        return null;
    }

    public function rules(): array
    {
        return [
            'term' => 'nullable',
            'id' => 'nullable',
            'campus_id' => 'nullable',
            'campus id' => 'nullable',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getSkippedBlankCount(): int
    {
        return $this->skippedBlank;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getRejectedCount(): int
    {
        return count($this->failures());
    }

    public function getErrorDetails(): array
    {
        $errors = [];
        foreach ($this->failures() as $failure) {
            $errors[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
        return $errors;
    }

    /**
     * @return array<int, array{id: int, action: string}>
     */
    public function getAffectedRows(): array
    {
        return $this->affectedRows;
    }

    /**
     * Normalize a program name using the mapping.
     */
    private function normalizeProgram(string $program, string $studentNumber, string $termId): string
    {
        $key = strtolower(trim($program));

        if (isset($this->programMap[$key])) {
            $normalized = $this->programMap[$key];
            $this->normalizedRows[] = [
                'student_number' => $studentNumber,
                'term_id' => $termId,
                'original' => $program,
                'normalized' => $normalized,
            ];
            return $normalized;
        }

        return $program;
    }

    public function getNormalizedRows(): array
    {
        return $this->normalizedRows;
    }

    public function getNormalizedCount(): int
    {
        return count($this->normalizedRows);
    }

    /**
     * Parse course codes from a courses_enrolled string and sync to pivot.
     */
    private function syncCourses(Enrollee $enrollee, string $coursesText): void
    {
        $courseIds = [];

        $noteMap = [];

        // Find all parenthetical annotations and the text before them
        $remaining = $coursesText;
        while (preg_match('/\(([^)]+)\)/', $remaining, $parenMatch, PREG_OFFSET_CAPTURE)) {
            $noteText = trim($parenMatch[1][0]);
            $beforeText = substr($remaining, 0, $parenMatch[0][1]);

            if (preg_match('/([A-Z]{2,})\s+(\d+[A-Z]?)\s*$/', $beforeText, $codeMatch)) {
                $code = $codeMatch[1] . ' ' . $codeMatch[2];
                $noteMap[$code] = $noteText;
            }
            elseif (preg_match('/\b([A-Z]{2,})\s*$/', $beforeText, $codeMatch)) {
                $noteMap[$codeMatch[1]] = $noteText;
            }

            $remaining = substr($remaining, 0, $parenMatch[0][1])
                       . substr($remaining, $parenMatch[0][1] + strlen($parenMatch[0][0]));
        }

        // Strip all parenthetical content for course code extraction
        $cleanedText = preg_replace('/\([^)]*\)/', ' ', $coursesText);

        // 1) Extract standard "PREFIX NUMBER" course codes (e.g. CED 210, DM 220)
        preg_match_all('/([A-Z]{2,})\s+(\d+[A-Z]?)/', $cleanedText, $matches, PREG_SET_ORDER);

        $stripped = $cleanedText;

        foreach ($matches as $match) {
            $courseCode = $match[1] . ' ' . $match[2];
            $courseCode = $this->courseCodeMap[$courseCode] ?? $courseCode;
            $course = EnrollmentCourse::firstOrCreate(['course_code' => $courseCode]);
            $pivotData = [];
            if (isset($noteMap[$courseCode])) {
                $pivotData['notes'] = $noteMap[$courseCode];
            }
            $courseIds[$course->id] = $pivotData;
            $stripped = str_replace($match[0], ' ', $stripped);
        }

        // 2) Extract standalone uppercase word course codes (e.g. RESIDENCY, THESIS)
        preg_match_all('/\b([A-Z]{2,})\b/', $stripped, $wordMatches, PREG_SET_ORDER);
        foreach ($wordMatches as $wm) {
            $courseCode = $wm[1];
            if (in_array($courseCode, ['Units', 'UNITS', 'OR'], true)) {
                continue;
            }
            $courseCode = $this->courseCodeMap[$courseCode] ?? $courseCode;
            $course = EnrollmentCourse::firstOrCreate(['course_code' => $courseCode]);
            $pivotData = [];
            if (isset($noteMap[$courseCode])) {
                $pivotData['notes'] = $noteMap[$courseCode];
            }
            $courseIds[$course->id] = $pivotData;
        }

        if (!empty($courseIds)) {
            $enrollee->enrollmentCourses()->sync($courseIds);
        }
    }
}
