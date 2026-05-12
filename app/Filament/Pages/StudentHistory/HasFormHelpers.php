<?php

namespace App\Filament\Pages\StudentHistory;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\Program;
use Filament\Forms;
use Illuminate\Support\Facades\DB;

/**
 * Shared form builder utilities and helper methods for the StudentHistory page.
 */
trait HasFormHelpers
{
    /**
     * Request-level caches for dropdown options to avoid redundant queries.
     */
    private static ?array $cachedFacultyOptions = null;
    private static ?array $cachedFacultyIdOptions = null;
    private static ?array $cachedFacultyNameOptions = null;
    private static ?array $cachedTermOptions = null;
    /**
     * Build a Select field that searches Faculty, with free-text fallback.
     */
    private function buildFacultySelect(string $name, string $label): Forms\Components\Select
    {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->options(function () {
                if (self::$cachedFacultyOptions !== null) return self::$cachedFacultyOptions;
                self::$cachedFacultyOptions = Faculty::orderBy('last_name')
                    ->get()
                    ->mapWithKeys(fn ($f) => [
                        $f->full_name => $f->full_name . ($f->designation ? " ({$f->designation})" : ''),
                    ])
                    ->toArray();
                return self::$cachedFacultyOptions;
            })
            ->searchable()
            ->placeholder('Select from faculty or type name...')
            ->createOptionForm([
                Forms\Components\TextInput::make('custom_name')
                    ->label('Name (not in faculty list)')
                    ->required()
                    ->maxLength(255),
            ])
            ->createOptionUsing(function (array $data): string {
                return $data['custom_name'];
            })
            ->getOptionLabelUsing(fn ($value) => $value);
    }

    /**
     * Build a Select field that uses Faculty ID as value (for FK columns).
     * Includes a "+" button to create a new Faculty record on the fly.
     */
    private function buildFacultyIdSelect(string $name, string $label): Forms\Components\Select
    {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->options(function () {
                if (self::$cachedFacultyIdOptions !== null) return self::$cachedFacultyIdOptions;
                $faculty = Faculty::orderBy('last_name')->get();
                $groups = ['Faculty' => [], 'External Members' => []];
                foreach ($faculty as $f) {
                    $label = $f->full_name . ($f->designation ? " ({$f->designation})" : '');
                    $group = $f->is_external ? 'External Members' : 'Faculty';
                    $groups[$group][$f->id] = $label;
                }
                self::$cachedFacultyIdOptions = array_filter($groups);
                return self::$cachedFacultyIdOptions;
            })
            ->searchable()
            ->placeholder('Select from faculty or type name...')
            ->createOptionForm([
                Forms\Components\TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('designation')
                    ->label('Designation (optional)')
                    ->placeholder('e.g. Ph.D., Dr., Prof.')
                    ->maxLength(100),
            ])
            ->createOptionUsing(function (array $data): int {
                $faculty = Faculty::create([
                    'last_name' => $data['last_name'],
                    'first_name' => $data['first_name'],
                    'designation' => $data['designation'] ?? 'External Member',
                    'is_external' => true,
                    'faculty_status' => 'active',
                ]);
                return $faculty->id;
            })
            ->getOptionLabelUsing(function ($value) {
                $f = Faculty::find($value);
                return $f ? $f->full_name . ($f->designation ? " ({$f->designation})" : '') : "Faculty #{$value}";
            });
    }

    /**
     * Build a Faculty Select that stores the faculty full_name (not ID).
     * Used in graduation info modals where committee data stores names.
     */
    private function buildFacultyNameSelect(string $name, string $label): Forms\Components\Select
    {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->options(function () {
                if (self::$cachedFacultyNameOptions !== null) return self::$cachedFacultyNameOptions;
                $faculty = Faculty::orderBy('last_name')->get();
                $groups = ['Faculty' => [], 'External Members' => []];
                foreach ($faculty as $f) {
                    $optionLabel = $f->full_name . ($f->designation ? " ({$f->designation})" : '');
                    $group = $f->is_external ? 'External Members' : 'Faculty';
                    $groups[$group][$f->full_name] = $optionLabel;
                }
                self::$cachedFacultyNameOptions = array_filter($groups);
                return self::$cachedFacultyNameOptions;
            })
            ->searchable()
            ->placeholder('Select from faculty or type name...')
            ->createOptionForm([
                Forms\Components\TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('designation')
                    ->label('Designation (optional)')
                    ->placeholder('e.g. Ph.D., Dr., Prof.')
                    ->maxLength(100),
            ])
            ->createOptionUsing(function (array $data): string {
                $faculty = Faculty::create([
                    'last_name' => $data['last_name'],
                    'first_name' => $data['first_name'],
                    'designation' => $data['designation'] ?? 'External Member',
                    'is_external' => true,
                    'faculty_status' => 'active',
                ]);
                return $faculty->full_name;
            });
    }

    /**
     * Build an adviser row: faculty select + appointed date + optional term selects.
     */
    private function buildAdviserRow(string $namePrefix, string $label, bool $includeTerm = false): \Filament\Schemas\Components\Component
    {
        $fields = [
            $this->buildFacultyIdSelect("{$namePrefix}_id", "Faculty Name"),
            Forms\Components\DatePicker::make("{$namePrefix}_appointed_date")
                ->label("Appointed Date"),
        ];

        if ($includeTerm) {
            $fields[] = $this->buildTermSelect("{$namePrefix}_term_start_id", "Term Start");
            $fields[] = $this->buildTermSelect("{$namePrefix}_term_end_id", "Term End");
        }

        return \Filament\Schemas\Components\Fieldset::make($label)
            ->schema($fields)
            ->columns($includeTerm ? 4 : 2)
            ->columnSpanFull();
    }

    /**
     * Build a member row: faculty select + optional role descriptor side by side.
     */
    private function buildMemberRow(int $index): array
    {
        $memberRoleOptions = [
            '' => 'Unspecified',
            'reader' => 'Reader',
            'statistician' => 'Statistician',
            'subject-specialist' => 'Subject Specialist',
            'technical-adviser' => 'Technical Adviser',
            'external-examiner' => 'External Examiner',
            'other' => 'Other',
        ];

        return [
            \Filament\Schemas\Components\Fieldset::make("Member {$index}")
                ->schema([
                    $this->buildFacultyIdSelect("member{$index}_id", "Faculty Name")
                        ->label("Faculty Name"),
                    Forms\Components\Select::make("member{$index}_role")
                        ->label("Role")
                        ->options($memberRoleOptions)
                        ->placeholder('Unspecified'),
                    Forms\Components\DatePicker::make("member{$index}_appointed")
                        ->label("Appointed Date"),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ];
    }

    /**
     * Build a semester select for term_start_id or term_end_id fields.
     */
    private function buildTermSelect(string $name, string $label): Forms\Components\Select
    {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->options(function () {
                if (self::$cachedTermOptions !== null) return self::$cachedTermOptions;
                self::$cachedTermOptions = \App\Models\Semester::with('academicYear')
                    ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                    ->toArray();
                return self::$cachedTermOptions;
            })
            ->searchable()
            ->placeholder('Select semester...');
    }

    /**
     * Fix encoding issues (mojibake) for names containing special characters like ñ.
     */
    private function fixEncoding(?string $value): ?string
    {
        if (!$value) return null;

        // Replace UTF-8 replacement character with Ñ (common for Filipino names)
        $value = str_replace("\xEF\xBF\xBD", 'Ñ', $value);

        // Try to convert from Latin-1 to UTF-8 if it appears broken
        if (!mb_check_encoding($value, 'UTF-8')) {
            $converted = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
            if ($converted) {
                $value = $converted;
            }
        }

        return $value;
    }

    private function normalizeSex(?string $sex): ?string
    {
        if (!$sex) return null;
        $lower = strtolower(trim($sex));
        return match (true) {
            in_array($lower, ['m', 'male']) => 'male',
            in_array($lower, ['f', 'female']) => 'female',
            default => 'other',
        };
    }

    private function normalizeMaritalStatus(?string $status): ?string
    {
        if (!$status) return null;
        $lower = strtolower(trim($status));
        return match (true) {
            in_array($lower, ['s', 'single']) => 'single',
            in_array($lower, ['m', 'married']) => 'married',
            in_array($lower, ['w', 'widowed']) => 'widowed',
            str_starts_with($lower, 'sep') => 'separated',
            str_starts_with($lower, 'div') => 'divorced',
            default => null,
        };
    }

    private function generatePlaceholderName(string $code): string
    {
        $upper = strtoupper(trim($code));
        return match (true) {
            str_contains($upper, 'RESID') => 'Residency',
            $upper === 'SP' => 'Special Problem',
            str_contains($upper, 'SPECIAL') => 'Special Problem',
            str_contains($upper, 'THESIS') => 'Thesis',
            str_contains($upper, 'DISSERT') => 'Dissertation',
            default => "Non-catalog Course ({$code})",
        };
    }

    /**
     * Estimate course units from curriculum data, with memoization.
     */
    private array $unitsMemo = [];

    private function estimateCourseUnits(string $courseCode): int
    {
        if (isset($this->unitsMemo[$courseCode])) {
            return $this->unitsMemo[$courseCode];
        }

        $units = DB::table('program_courses as pc')
            ->join('courses as c', 'c.id', '=', 'pc.course_id')
            ->where('c.course_code', $courseCode)
            ->whereNotNull('pc.units')
            ->value('pc.units');

        if ($units) {
            $this->unitsMemo[$courseCode] = (int) $units;
            return (int) $units;
        }

        $upper = strtoupper($courseCode);
        if (str_contains($upper, 'RESID') || $upper === 'SP') {
            $this->unitsMemo[$courseCode] = 0;
            return 0;
        }

        $this->unitsMemo[$courseCode] = 3;
        return 3;
    }

    /**
     * Derive degree level (MS/MA/PhD etc) from program name.
     */
    private function deriveDegreeLevel(?string $programName): string
    {
        if (!$programName) return '';

        $lower = strtolower($programName);
        if (str_contains($lower, 'doctor of philosophy')) return 'PhD';
        if (str_contains($lower, 'doctor of public administration')) return 'DPA';
        if (str_contains($lower, 'master of science')) return 'MS';
        if (str_contains($lower, 'master of arts')) return 'MA';
        if (str_contains($lower, 'master of public affairs in education')) return 'MPAEM';
        if (str_contains($lower, 'master of public affairs') || str_contains($lower, 'master in public affairs')) return 'MPA';
        if (str_contains($lower, 'master')) return 'MS';
        if (str_contains($lower, 'doctor')) return 'PhD';

        return '';
    }

    /**
     * Get degree options from existing graduates + programs.
     */
    private function getDegreeOptions(): array
    {
        // Pull unique degrees from graduates table (real data)
        $existing = \App\Models\Graduate::select('degree')
            ->distinct()
            ->whereNotNull('degree')
            ->where('degree', '!=', '')
            ->orderBy('degree')
            ->pluck('degree')
            ->mapWithKeys(fn ($d) => [$d => $d])
            ->toArray();

        return $existing;
    }

    /**
     * Get program name options from programs table.
     */
    private function getMajorFieldOptions(): array
    {
        return Program::orderBy('name')->pluck('name', 'name')->toArray();
    }

    /**
     * Check if a course code is a thesis-like course (thesis, dissertation, field study).
     */
    private function isThesisLikeCourse(string $courseCode): bool
    {
        $upper = strtoupper(trim($courseCode));
        return (
            str_contains($upper, 'THESIS') ||
            str_contains($upper, 'DISSERT') ||
            str_contains($upper, 'FIELD') ||
            str_contains($upper, 'SP ') ||
            $upper === 'SP' ||
            str_contains($upper, 'SPECIAL')
        );
    }

    /**
     * Check if a course code is a residency/non-credit course.
     */
    private function isResidencyCourse(string $courseCode): bool
    {
        $upper = strtoupper(trim($courseCode));
        return (
            str_contains($upper, 'RESID') ||
            str_contains($upper, 'AUDIT') ||
            str_contains($upper, 'NON-CRED')
        );
    }

    /**
     * Convert a term code to a semester label.
     */
    private function termCodeToLabel($termCode): ?string
    {
        if (!$termCode) return null;

        $semester = \App\Models\Semester::where('term_code', $termCode)->first();
        return $semester?->label;
    }

    /**
     * Parse a comma-separated course list string into individual course codes.
     */
    private function parseCourseList(string $raw): array
    {
        $courses = [];
        $items = preg_split('/[,;]+/', $raw);
        foreach ($items as $item) {
            $clean = trim($item);
            if (!empty($clean)) {
                $courses[] = $clean;
            }
        }
        return $courses;
    }

    /**
     * Match a degree program string to a programs.id.
     * Delegates to ProgramMatcher service.
     */
    private function matchProgramForStudent(?string $degreeProgram): ?int
    {
        return \App\Services\ProgramMatcher::instance()->match($degreeProgram);
    }
}
