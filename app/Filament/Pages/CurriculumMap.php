<?php

namespace App\Filament\Pages;

use App\Enums\CourseType;
use App\Enums\DegreeLevel;
use App\Models\Course;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\ProgramRequirement;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;


class CurriculumMap extends Page
{

    protected static string|UnitEnum|null $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 5;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Curriculum Map';

    protected static ?string $title = 'Curriculum Map';

    protected static ?string $slug = 'curriculum-map';

    protected string $view = 'filament.pages.curriculum-map';

    public ?int $selectedProgramId = null;

    public string $viewMode = 'timeline'; // 'timeline' or 'table'

    // ── Memoization cache (cleared after mutations) ──
    protected ?array $_cachedCurriculum = null;
    protected ?Program $_cachedProgram = null;
    protected bool $_programLoaded = false;
    protected ?\Illuminate\Support\Collection $_cachedAvailableCourses = null;
    protected ?array $_cachedMinUnits = null;
    protected ?array $_cachedTypeStats = null;
    protected ?\Illuminate\Support\Collection $_cachedRequirements = null;

    /**
     * Reset all memoized caches. Called after any data mutation.
     */
    protected function resetComputedCache(): void
    {
        $this->_cachedCurriculum = null;
        $this->_cachedProgram = null;
        $this->_programLoaded = false;
        $this->_cachedAvailableCourses = null;
        $this->_cachedMinUnits = null;
        $this->_cachedTypeStats = null;
        $this->_cachedRequirements = null;
    }

    public function mount(): void
    {
        $this->selectedProgramId = Program::first()?->id;
    }

    public function updatedSelectedProgramId(): void
    {
        $this->resetComputedCache();
    }

    public function getPrograms(): \Illuminate\Support\Collection
    {
        return Program::orderBy('name')->get();
    }

    public function getCurriculum(): array
    {
        if ($this->_cachedCurriculum !== null) {
            return $this->_cachedCurriculum;
        }

        if (! $this->selectedProgramId) {
            return $this->_cachedCurriculum = [];
        }

        $mappings = ProgramCourse::with(['course', 'programMajor', 'cognateField'])
            ->where('program_id', $this->selectedProgramId)
            ->get();

        $grouped = [];
        foreach ($mappings as $mapping) {
            $course = $mapping->course;
            if (! $course) continue;

            $pivotType = $mapping->course_type;
            $typeLabel = $pivotType instanceof CourseType
                ? $pivotType->label()
                : ($pivotType ? (CourseType::tryFrom($pivotType)?->label() ?? 'Other') : 'Other');

            $specLabel = $mapping->programMajor?->name ?? 'General';

            // Core courses are taken regardless of specialization — always group as General
            if ($typeLabel === 'Core') {
                $specLabel = 'General';
            }

            // For Cognate courses, group by cognate field name instead of specialization
            if ($typeLabel === 'Cognate' && $mapping->cognateField?->name) {
                $specLabel = $mapping->cognateField->name;
            }

            if (! isset($grouped[$typeLabel])) {
                $grouped[$typeLabel] = [];
            }
            if (! isset($grouped[$typeLabel][$specLabel])) {
                $grouped[$typeLabel][$specLabel] = [];
            }

            // Detect MS-conditional from notes
            $notes = $mapping->notes ?? '';
            $msConditional = false;
            if ($notes && (
                stripos($notes, 'not taken at the MS') !== false ||
                stripos($notes, 'not taken in their MS') !== false ||
                stripos($notes, 'if not taken') !== false && stripos($notes, 'MS') !== false
            )) {
                $msConditional = true;
            }

            $grouped[$typeLabel][$specLabel][] = [
                'mapping_id' => $mapping->id,
                'course_id' => $course->id,
                'code' => $course->course_code,
                'name' => $course->course_name,
                'units' => $mapping->units,
                'semester' => $mapping->semester_offered,
                'prerequisite' => $mapping->prerequisite_text,
                'description' => $mapping->description,
                'notes' => $notes ?: null,
                'cognate_field' => $mapping->cognateField?->name,
                'year_level' => $mapping->year_level,
                'is_required' => $mapping->is_required,
                'ms_conditional' => $msConditional,
                'choice_group' => null,
            ];
        }

        // Deduplicate: same course_id appearing multiple times in the same type+spec group
        foreach ($grouped as $typeLabel => &$specializations) {
            foreach ($specializations as $specLabel => &$courses) {
                $seen = [];
                $courses = array_values(array_filter($courses, function ($c) use (&$seen) {
                    if (isset($seen[$c['course_id']])) return false;
                    $seen[$c['course_id']] = true;
                    return true;
                }));
            }
        }
        unset($specializations, $courses);

        // Sort by standard course type order
        $typeOrder = ['Core', 'Prescribed', 'Major', 'Specialization', 'Cognate', 'Elective', 'Seminar', 'Thesis', 'Dissertation', 'Field Study'];
        uksort($grouped, function ($a, $b) use ($typeOrder) {
            $posA = array_search($a, $typeOrder);
            $posB = array_search($b, $typeOrder);
            if ($posA === false) $posA = 99;
            if ($posB === false) $posB = 99;
            return $posA - $posB;
        });

        // Inject empty course types from program requirements so sections appear even when empty
        $typeKeywords = [
            'core' => 'Core',
            'prescribed' => 'Prescribed',
            'major' => 'Major',
            'specialization' => 'Specialization',
            'cognate' => 'Cognate',
            'elective' => 'Elective',
            'seminar' => 'Seminar',
            'thesis' => 'Thesis',
            'dissertation' => 'Dissertation',
            'field study' => 'Field Study',
        ];
        $requirements = $this->getProgramRequirements();
        $program = $this->getSelectedProgram();
        foreach ($requirements as $req) {
            $text = strtolower($req->requirement_text);
            foreach ($typeKeywords as $kw => $typeLabel) {
                if (str_contains($text, $kw)) {
                    $targetLabel = $typeLabel;
                    
                    // Doctorate programs use "Dissertation" instead of "Thesis"
                    if ($typeLabel === 'Thesis' && $program?->degree_level === DegreeLevel::Doctorate) {
                        $targetLabel = 'Dissertation';
                    }

                    if (!isset($grouped[$targetLabel])) {
                        $grouped[$targetLabel] = ['General' => []];
                    }
                }
            }
        }

        // Re-sort after injecting empty types
        uksort($grouped, function ($a, $b) use ($typeOrder) {
            $posA = array_search($a, $typeOrder);
            $posB = array_search($b, $typeOrder);
            if ($posA === false) $posA = 99;
            if ($posB === false) $posB = 99;
            return $posA - $posB;
        });

        // Sort cognate field sub-groups alphabetically
        if (isset($grouped['Cognate'])) {
            ksort($grouped['Cognate']);
        }

        // Detect choice groups: courses whose notes reference another course's code
        $choiceGroupId = 0;
        foreach ($grouped as $typeLabel => &$specializations) {
            foreach ($specializations as $specLabel => &$courses) {
                $count = count($courses);
                for ($i = 0; $i < $count; $i++) {
                    if ($courses[$i]['choice_group'] !== null) continue;
                    if (empty($courses[$i]['notes'])) continue;

                    for ($j = $i + 1; $j < $count; $j++) {
                        if ($courses[$j]['choice_group'] !== null) continue;
                        
                        $iCode = $courses[$i]['code'];
                        $jCode = $courses[$j]['code'];
                        $iNotes = $courses[$i]['notes'] ?? '';
                        $jNotes = $courses[$j]['notes'] ?? '';

                        $iMentionsJ = stripos($iNotes, $jCode) !== false;
                        $jMentionsI = stripos($jNotes, $iCode) !== false;

                        if ($iMentionsJ || $jMentionsI) {
                            $choiceGroupId++;
                            $courses[$i]['choice_group'] = $choiceGroupId;
                            $courses[$j]['choice_group'] = $choiceGroupId;
                        }
                    }
                }
            }
        }
        unset($specializations, $courses);

        return $this->_cachedCurriculum = $grouped;
    }

    public function getSelectedProgram(): ?Program
    {
        if ($this->_programLoaded) {
            return $this->_cachedProgram;
        }

        $this->_programLoaded = true;

        if (! $this->selectedProgramId) {
            return $this->_cachedProgram = null;
        }

        return $this->_cachedProgram = Program::find($this->selectedProgramId);
    }

    public function getProgramRequirements(): \Illuminate\Support\Collection
    {
        if ($this->_cachedRequirements !== null) {
            return $this->_cachedRequirements;
        }

        if (! $this->selectedProgramId) {
            return $this->_cachedRequirements = collect();
        }
        
        return $this->_cachedRequirements = ProgramRequirement::where('program_id', $this->selectedProgramId)
            ->orderBy('sort_order')
            ->get();
    }

    public function getTotalUnits(): ?int
    {
        $program = $this->getSelectedProgram();
        // Prefer override, then stored total, then auto-sum
        if ($program?->total_units_override) {
            return $program->total_units_override;
        }
        if ($program?->total_units_required) {
            return $program->total_units_required;
        }
        $minUnits = $this->getMinUnitsPerType();
        $sum = array_sum($minUnits);
        return $sum > 0 ? $sum : null;
    }

    public function getTotalCourses(): int
    {
        if (! $this->selectedProgramId) return 0;

        return ProgramCourse::where('program_id', $this->selectedProgramId)
            ->whereHas('course')
            ->count();
    }

    public function removeCourseMapping(int $mappingId): void
    {
        ProgramCourse::find($mappingId)?->delete();
        $this->resetComputedCache();
    }

    /**
     * Update the mapped units for a specific course in the curriculum.
     */
    public function updateCourseMappingUnits(int $mappingId, int|string|null $units): void
    {
        $mapping = ProgramCourse::find($mappingId);
        if (! $mapping || $mapping->program_id !== $this->selectedProgramId) return;

        // If explicitly set to 0, use null to reset to system default
        $unitsToSet = ($units === null || $units === '' || (int)$units <= 0) ? null : (int)$units;
        
        $mapping->update(['units' => $unitsToSet]);
        $this->resetComputedCache();
    }

    /**
     * Add a course to the current program's curriculum.
     */
    public function addCourseMapping(int $courseId, string $courseType): void
    {
        if (! $this->selectedProgramId) return;

        // Accept either enum value ('core') or label ('Core') or display label ('Specialization')
        $typeEnum = CourseType::tryFrom($courseType)
            ?? CourseType::tryFrom(strtolower($courseType))
            ?? collect(CourseType::cases())->first(fn ($c) => $c->label() === $courseType);

        if (! $typeEnum) return;

        // Check if already mapped (any specialization)
        $exists = ProgramCourse::where('program_id', $this->selectedProgramId)
            ->where('course_id', $courseId)
            ->exists();

        if ($exists) return;

        // Check if curriculum was empty before adding (for redirect workaround)
        $wasEmpty = ProgramCourse::where('program_id', $this->selectedProgramId)->count() === 0;

        ProgramCourse::create([
            'program_id' => $this->selectedProgramId,
            'course_id' => $courseId,
            'course_type' => $typeEnum->value,
        ]);

        $this->resetComputedCache();

        // When transitioning from empty → populated, morphdom cannot reconcile
        // the DOM change (Filament section → timeline). Force a hard browser reload.
        if ($wasEmpty) {
            $this->js('setTimeout(() => window.location.reload(), 100)');
        }
    }

    /**
     * Get courses not yet mapped to the selected program.
     */
    public function getAvailableCourses(): \Illuminate\Support\Collection
    {
        if ($this->_cachedAvailableCourses !== null) {
            return $this->_cachedAvailableCourses;
        }

        if (! $this->selectedProgramId) {
            return $this->_cachedAvailableCourses = collect();
        }

        $mappedIds = ProgramCourse::where('program_id', $this->selectedProgramId)
            ->pluck('course_id');

        return $this->_cachedAvailableCourses = Course::whereNotIn('id', $mappedIds)
            ->where('is_active', true)
            ->orderBy('course_code')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'label' => "{$c->course_code} — {$c->course_name}",
            ]);
    }

    public function getEditCourseUrl(int $courseId): string
    {
        return route('filament.admin.resources.courses.edit', ['record' => $courseId]);
    }

    /**
     * Parse program requirements to extract minimum units per course type.
     */
    public function getMinUnitsPerType(): array
    {
        if ($this->_cachedMinUnits !== null) {
            return $this->_cachedMinUnits;
        }

        $program = $this->getSelectedProgram();
        if (! $program) return $this->_cachedMinUnits = [];

        // 1. Parse requirement text via regex (baseline values)
        $requirements = $this->getProgramRequirements();
        $minUnits = [];

        $typeKeywords = [
            'core' => 'Core',
            'prescribed' => 'Prescribed',
            'major' => 'Major',
            'area of specialization' => 'Specialization',
            'specialization' => 'Specialization',
            'cognate' => 'Cognate',
            'elective' => 'Elective',
            'seminar' => 'Seminar',
            'thesis' => 'Thesis',
            'dissertation' => 'Dissertation',
            'field studies' => 'Field Study',
            'field study' => 'Field Study',
        ];

        // First pass: collect all matches with specificity info
        $matches = [];
        foreach ($requirements as $req) {
            $text = $req->requirement_text;
            $lower = strtolower($text);

            if (preg_match('/\b(\d+)\s*units?\b/i', $text, $m)
                || preg_match('/\b(?:at\s+least|minimum\s+of)\b.*?\(?(\d+)\)?/i', $text, $m)) {
                $units = (int) $m[1];

                // Detect if this is a sub-item (starts with number+period like "1." or "2.")
                $isSubItem = preg_match('/^\s*\d+\.\s/', $text);

                foreach ($typeKeywords as $kw => $typeLabel) {
                    if (str_contains($lower, $kw)) {
                        $matches[] = [
                            'type' => $typeLabel,
                            'units' => $units,
                            'is_sub' => $isSubItem,
                        ];
                        break;
                    }
                }
            }
        }

        // Second pass: sub-items override parent items for the same type
        foreach ($matches as $match) {
            $type = $match['type'];
            $units = $match['units'];
            $isSub = $match['is_sub'];

            if (!isset($minUnits[$type])) {
                $minUnits[$type] = $units;
            } elseif ($isSub) {
                // Sub-item always wins — it's more specific
                $minUnits[$type] = $units;
            }
            // Parent item does NOT overwrite a sub-item
        }

        // 2. Merge explicit DB overrides on top (takes priority over parsed)
        $overrides = $program->min_units_per_type ?? [];
        foreach ($overrides as $type => $val) {
            if ($val !== null && $val > 0) {
                $minUnits[$type] = (int) $val;
            }
        }

        return $this->_cachedMinUnits = $minUnits;
    }

    /**
     * Update the min units for a specific course type (inline edit from curriculum map).
     */
    public function updateMinUnits(string $courseType, ?int $units): void
    {
        $program = Program::find($this->selectedProgramId);
        if (! $program) return;

        // Start from current overrides or parsed values
        $current = $program->min_units_per_type ?? $this->getMinUnitsPerType();

        if ($units === null || $units === 0) {
            unset($current[$courseType]);
        } else {
            $current[$courseType] = $units;
        }

        // Auto-sum the total (only if no manual override)
        $updateData = ['min_units_per_type' => $current];
        if ($program->total_units_override === null) {
            $updateData['total_units_required'] = array_sum($current);
        }

        $program->update($updateData);
        $this->resetComputedCache();
    }

    /**
     * Update the total required units override (inline edit from Total Required pill).
     */
    public function updateTotalOverride(?int $units): void
    {
        $program = Program::find($this->selectedProgramId);
        if (! $program) return;

        if ($units === null || $units === 0) {
            // Clear override — revert to auto-sum
            $minUnits = $this->getMinUnitsPerType();
            $program->update([
                'total_units_override' => null,
                'total_units_required' => array_sum($minUnits),
            ]);
        } else {
            // Set manual override
            $program->update([
                'total_units_override' => $units,
                'total_units_required' => $units,
            ]);
        }

        $this->resetComputedCache();
    }

    /**
     * Re-parse all program requirement text rows and sync the result
     * into programs.min_units_per_type. Called after any requirement
     * text is added, updated, or deleted so the unit counts stay
     * consistent with the displayed requirement text.
     */
    protected function syncMinUnitsFromRequirements(): void
    {
        $program = Program::find($this->selectedProgramId);
        if (! $program) return;

        // Clear the cached requirements so we re-read from DB
        $this->_cachedRequirements = null;
        $this->_cachedMinUnits = null;

        // Re-parse requirements text (this does NOT read the DB override — we build fresh)
        $requirements = $this->getProgramRequirements();
        $parsed = [];

        $typeKeywords = [
            'core' => 'Core',
            'prescribed' => 'Prescribed',
            'major' => 'Major',
            'area of specialization' => 'Specialization',
            'specialization' => 'Specialization',
            'cognate' => 'Cognate',
            'elective' => 'Elective',
            'seminar' => 'Seminar',
            'thesis' => 'Thesis',
            'dissertation' => 'Dissertation',
            'field studies' => 'Field Study',
            'field study' => 'Field Study',
        ];

        $matches = [];
        foreach ($requirements as $req) {
            $text = $req->requirement_text;
            $lower = strtolower($text);

            if (preg_match('/\b(\d+)\s*units?\b/i', $text, $m)
                || preg_match('/\b(?:at\s+least|minimum\s+of)\b.*?\(?(\d+)\)?/i', $text, $m)) {
                $units = (int) $m[1];
                $isSubItem = preg_match('/^\s*\d+\.\s/', $text);

                foreach ($typeKeywords as $kw => $typeLabel) {
                    if (str_contains($lower, $kw)) {
                        $matches[] = [
                            'type' => $typeLabel,
                            'units' => $units,
                            'is_sub' => $isSubItem,
                        ];
                        break;
                    }
                }
            }
        }

        foreach ($matches as $match) {
            $type = $match['type'];
            $units = $match['units'];
            $isSub = $match['is_sub'];

            if (!isset($parsed[$type])) {
                $parsed[$type] = $units;
            } elseif ($isSub) {
                $parsed[$type] = $units;
            }
        }

        // Write the freshly parsed values into the DB
        $updateData = ['min_units_per_type' => $parsed];
        if ($program->total_units_override === null) {
            $updateData['total_units_required'] = array_sum($parsed);
        }
        $program->update($updateData);
    }

    /**
     * Get per-type course and unit counts for the summary stats bar.
     */
    public function getTypeStats(): array
    {
        if ($this->_cachedTypeStats !== null) {
            return $this->_cachedTypeStats;
        }

        $curriculum = $this->getCurriculum();
        $minUnitsMap = $this->getMinUnitsPerType();
        $stats = [];
        $totalActualUnits = 0;

        foreach ($curriculum as $type => $specializations) {
            $actualUnits = 0;
            foreach ($specializations as $courses) {
                foreach ($courses as $c) {
                    $actualUnits += (int) ($c['units'] ?? 0);
                }
            }
            $totalActualUnits += $actualUnits;
            $minUnits = $minUnitsMap[$type] ?? null;

            $stats[] = [
                'type' => $type,
                'actual_units' => $actualUnits,
                'min_units' => $minUnits,
                'color' => self::getTypeColor($type),
            ];
        }

        // Append total summary
        $program = $this->getSelectedProgram();
        $totalOverride = $program?->total_units_override;
        $totalRequired = $totalOverride ?? $program?->total_units_required;
        $hasOverride = $totalOverride !== null;

        $stats[] = [
            'type' => '__total__',
            'actual_units' => $totalActualUnits,
            'min_units' => $totalRequired,
            'has_override' => $hasOverride,
            'color' => '#1A5C38',
        ];

        return $this->_cachedTypeStats = $stats;
    }

    /**
     * Map course type label to a hex color (for timeline nodes and card accents).
     */
    public static function getTypeColor(string $type): string
    {
        return match ($type) {
            'Core' => '#1A5C38',
            'Prescribed' => '#4338CA',
            'Major' => '#1B4D3E',
            'Specialization' => '#7C3AED',
            'Cognate' => '#92400E',
            'Elective' => '#4CAF7D',
            'Seminar' => '#F0AD4E',
            'Thesis' => '#DC3545',
            'Dissertation' => '#DC3545',
            'Field Study' => '#0D9488',
            default => '#6b7280',
        };
    }

    /**
     * Lighter variant for dark mode accents.
     */
    public static function getTypeColorLight(string $type): string
    {
        return match ($type) {
            'Core' => '#4CAF7D',
            'Prescribed' => '#818CF8',
            'Major' => '#4CAF7D',
            'Specialization' => '#C4B5FD',
            'Cognate' => '#FBBF24',
            'Elective' => '#A7F3D0',
            'Seminar' => '#FDE68A',
            'Thesis' => '#FCA5A5',
            'Dissertation' => '#FCA5A5',
            'Field Study' => '#5EEAD4',
            default => '#9CA3AF',
        };
    }

    /**
     * Short label for timeline node dots.
     */
    public static function getTypeShortLabel(string $type): string
    {
        return match ($type) {
            'Core' => 'C',
            'Prescribed' => 'P',
            'Major' => 'M',
            'Specialization' => 'Sp',
            'Cognate' => 'Cg',
            'Elective' => 'E',
            'Seminar' => 'S',
            'Thesis' => 'T',
            'Dissertation' => 'D',
            'Field Study' => 'FS',
            default => '?',
        };
    }



    /**
     * Filament Action: Create a new program via native modal.
     */
    public function createProgramAction(): Action
    {
        return Action::make('createProgram')
            ->label('Create Program')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading('Create New Program')
            ->modalWidth('md')
            ->form([
                TextInput::make('code')
                    ->label('Program Code')
                    ->placeholder('e.g., PhD-DVST')
                    ->maxLength(20)
                    ->required(),
                TextInput::make('name')
                    ->label('Program Name')
                    ->placeholder('e.g., Doctor of Philosophy in Development Studies')
                    ->required(),
                Select::make('degree_level')
                    ->label('Degree Level')
                    ->options(
                        collect(DegreeLevel::cases())
                            ->mapWithKeys(fn (DegreeLevel $level) => [$level->value => $level->label()])
                            ->toArray()
                    )
                    ->default('master')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $level = DegreeLevel::tryFrom($data['degree_level']);
                if (! $level || ! $data['code'] || ! $data['name']) return;

                if (Program::where('code', $data['code'])->exists()) {
                    Notification::make()
                        ->title('Duplicate program code')
                        ->body("A program with code \"{$data['code']}\" already exists.")
                        ->danger()
                        ->send();
                    return;
                }

                $program = Program::create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'degree_level' => $level->value,
                    'is_active' => true,
                ]);

                $this->selectedProgramId = $program->id;
                $this->resetComputedCache();

                Notification::make()
                    ->title('Program created')
                    ->body("Program \"{$program->code}\" has been created.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Quick-create a new program from the Curriculum Map page.
     * @deprecated Use createProgramAction() instead.
     */
    public function createProgram(string $code, string $name, string $degreeLevel): void
    {
        $level = DegreeLevel::tryFrom($degreeLevel);
        if (! $level || ! $code || ! $name) return;

        // Prevent duplicate codes
        if (Program::where('code', $code)->exists()) return;

        $program = Program::create([
            'code' => $code,
            'name' => $name,
            'degree_level' => $level->value,
            'is_active' => true,
        ]);

        $this->selectedProgramId = $program->id;
        $this->resetComputedCache();
    }

    /**
     * Get curriculum grouped by semester for table view mode.
     */
    public function getCurriculumBySemester(): array
    {
        $curriculum = $this->getCurriculum();
        $bySemester = [];

        foreach ($curriculum as $type => $specializations) {
            foreach ($specializations as $specName => $courses) {
                foreach ($courses as $course) {
                    $sem = $course['semester'] ?: 'Unspecified';
                    if (! isset($bySemester[$sem])) {
                        $bySemester[$sem] = [];
                    }
                    $course['type_label'] = $type;
                    $course['type_color'] = self::getTypeColor($type);
                    $course['spec_name'] = $specName;
                    $bySemester[$sem][] = $course;
                }
            }
        }

        // Custom semester sort order
        $semesterOrder = [
            'First Semester' => 1,
            'Second Semester' => 2,
            'First and Second Semester' => 3,
            'Midyear' => 4,
            'First Semester, Second Semester, and Midyear' => 5,
            'Unspecified' => 99,
        ];

        uksort($bySemester, function ($a, $b) use ($semesterOrder) {
            $posA = $semesterOrder[$a] ?? 50;
            $posB = $semesterOrder[$b] ?? 50;
            return $posA - $posB;
        });

        return $bySemester;
    }

    /**
     * Add a program requirement to the current program.
     */
    public function addRequirement(string $text): void
    {
        if (! $this->selectedProgramId || ! trim($text)) return;

        $maxSort = ProgramRequirement::where('program_id', $this->selectedProgramId)
            ->max('sort_order') ?? -1;

        ProgramRequirement::create([
            'program_id' => $this->selectedProgramId,
            'requirement_text' => trim($text),
            'sort_order' => $maxSort + 1,
        ]);

        $this->syncMinUnitsFromRequirements();
        $this->resetComputedCache();
    }

    /**
     * Update a program requirement's text.
     */
    public function updateRequirement(int $requirementId, string $text): void
    {
        $req = ProgramRequirement::find($requirementId);
        if (! $req || $req->program_id !== $this->selectedProgramId) return;

        $req->update(['requirement_text' => trim($text)]);
        $this->syncMinUnitsFromRequirements();
        $this->resetComputedCache();
    }

    /**
     * Delete a program requirement.
     */
    public function deleteRequirement(int $requirementId): void
    {
        $req = ProgramRequirement::find($requirementId);
        if (! $req || $req->program_id !== $this->selectedProgramId) return;

        $req->delete();
        $this->syncMinUnitsFromRequirements();
        $this->resetComputedCache();
    }

    /**
     * Download curriculum map as PDF.
     */
    public function downloadPdf()
    {
        $program = Program::find($this->selectedProgramId);
        if (! $program) return;

        $curriculum = $this->getCurriculum();
        $stats = $this->getTypeStats();
        $requirements = $this->getProgramRequirements();

        // Build semester data for table view
        $semesterData = [];
        if ($this->viewMode === 'table') {
            $semesterData = $this->getCurriculumBySemester();
        }

        $pdf = Pdf::loadView('filament.pages.curriculum-map-pdf', [
            'program' => $program,
            'curriculum' => $curriculum,
            'stats' => $stats,
            'requirements' => $requirements,
            'viewMode' => $this->viewMode,
            'semesterData' => $semesterData,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = str($program->code)->slug() . '-curriculum-map.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
