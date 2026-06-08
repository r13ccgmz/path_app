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
use Livewire\Attributes\Url;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Schemas\Components\Section;
use App\Models\ProgramMajor;
use App\Models\CognateField;
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

    #[Url(as: 'program')]
    public ?int $selectedProgramId = null;

    public string $viewMode = 'timeline'; // 'timeline' or 'table'

    public ?string $addCourseDefaultType = null;

    public ?int $editingMappingId = null;

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
        if (!$this->selectedProgramId) {
            $this->selectedProgramId = Program::first()?->id;
        }
    }

    public function updatedSelectedProgramId(): void
    {
        $this->resetComputedCache();
        $this->addCourseDefaultType = null;
        $this->editingMappingId = null;
        $this->dispatch('program-switched');
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

        // Inject empty course types from program requirements and configured min units so sections appear even when empty
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

        if ($program && !empty($program->min_units_per_type)) {
            foreach ($program->min_units_per_type as $typeLabel => $val) {
                if ($val !== null && $val > 0) {
                    if (!isset($grouped[$typeLabel])) {
                        $grouped[$typeLabel] = ['General' => []];
                    }
                }
            }
        }

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
        $this->dispatch('curriculum-updated');
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
        $this->dispatch('curriculum-updated');

        // When transitioning from empty → populated, morphdom cannot reconcile
        // the DOM change (Filament section → timeline). Force a hard browser reload.
        if ($wasEmpty) {
            $this->js('setTimeout(() => window.location.href = "' . static::getUrl(['program' => $this->selectedProgramId]) . '", 100)');
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

    public function updateMinUnits(string $courseType, ?int $units): void
    {
        $program = Program::find($this->selectedProgramId);
        if (! $program) return;

        // Start from current overrides or parsed values
        $current = $program->min_units_per_type ?? $this->getMinUnitsPerType();
        $minUnitsSum = array_sum($current);
        $hasCustomTotal = $program->total_units_required !== $minUnitsSum;

        if ($units === null || $units === 0) {
            unset($current[$courseType]);
        } else {
            $current[$courseType] = $units;
        }

        // Auto-sum the total (only if no manual override)
        $updateData = ['min_units_per_type' => $current];
        if (!$hasCustomTotal) {
            $updateData['total_units_required'] = array_sum($current);
        }

        $program->update($updateData);
        $this->resetComputedCache();
        $this->dispatch('curriculum-updated');
    }

    public function updateTotalOverride(?int $units): void
    {
        $program = Program::find($this->selectedProgramId);
        if (! $program) return;

        if ($units === null || $units === 0) {
            // Clear override — revert to auto-sum
            $minUnits = $this->getMinUnitsPerType();
            $program->update([
                'total_units_required' => array_sum($minUnits),
            ]);
        } else {
            // Set manual override
            $program->update([
                'total_units_required' => $units,
            ]);
        }

        $this->resetComputedCache();
        $this->dispatch('curriculum-updated');
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

        $minUnits = $program->min_units_per_type ?? [];
        $minUnitsSum = array_sum($minUnits);
        $hasCustomTotal = $program->total_units_required !== $minUnitsSum;

        // Write the freshly parsed values into the DB
        $updateData = ['min_units_per_type' => $parsed];
        if (!$hasCustomTotal) {
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
        $totalRequired = $program?->total_units_required;

        $minUnits = $program?->min_units_per_type ?? [];
        $minUnitsSum = array_sum($minUnits);
        $hasOverride = $program && ($totalRequired !== $minUnitsSum);

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





    protected function getHeaderActions(): array
    {
        return [];
    }

    public function createProgramAction(): Action
    {
        return Action::make('createProgram')
            ->label('Create Program')
            ->modalHeading('Create New Program')
            ->modalWidth('xl')
            ->fillForm(fn () => [
                'code' => null,
                'name' => null,
                'degree_level' => null,
                'is_active' => true,
                'max_residency_years' => null,
                'description' => null,
                'has_custom_total' => false,
                'total_units_required' => null,
                'min_units_Core' => null,
                'min_units_Prescribed' => null,
                'min_units_Major' => null,
                'min_units_Specialization' => null,
                'min_units_Cognate' => null,
                'min_units_Elective' => null,
                'min_units_Seminar' => null,
                'min_units_Thesis' => null,
                'min_units_Dissertation' => null,
                'min_units_FieldStudy' => null,
                'majors' => [],
                'requirements' => [],
            ])
            ->form([
                Section::make('Program Details')
                    ->collapsible()
                    ->columns(6)
                    ->schema([
                        TextInput::make('code')
                            ->label('Program Code')
                            ->required()
                            ->maxLength(20)
                            ->unique(table: 'programs', column: 'code')
                            ->placeholder('e.g., PhD-DVST')
                            ->columnSpan(2),
                        Select::make('degree_level')
                            ->label('Degree Level')
                            ->options(DegreeLevel::class)
                            ->required()
                            ->columnSpan(2),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                        TextInput::make('name')
                            ->label('Program Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Doctor of Philosophy in Development Studies')
                            ->columnSpan(6),
                        TextInput::make('max_residency_years')
                            ->label('Max Residency (years)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('e.g., 5')
                            ->columnSpan(3),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpan(6),
                    ]),
                Section::make('Specializations / Majors')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('majors')
                            ->hiddenLabel()
                            ->grid(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Specialization Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Local Governance and Development'),
                                TextInput::make('description')
                                    ->label('Description')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Optional description'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Minimum Units Required per Course Type')
                    ->description('Set the minimum required units for each course type.')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('min_units_Core')->label('Core')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Prescribed')->label('Prescribed')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Major')->label('Major')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Specialization')->label('Specialization')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Cognate')->label('Cognate')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Elective')->label('Elective')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Seminar')->label('Seminar')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Thesis')->label('Thesis')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Dissertation')->label('Dissertation')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_FieldStudy')->label('Field Study')->numeric()->placeholder('e.g., 0'),
                        Toggle::make('has_custom_total')
                            ->label('Set Total Units Manually')
                            ->default(false)
                            ->live()
                            ->columnSpan(2),
                        TextInput::make('total_units_required')
                            ->label('Total Units Required')
                            ->numeric()
                            ->required(fn ($get) => $get('has_custom_total'))
                            ->visible(fn ($get) => $get('has_custom_total'))
                            ->columnSpan(2),
                    ]),
                Section::make('Requirements')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('requirements')
                            ->hiddenLabel()
                            ->simple(
                                TextInput::make('requirement_text')
                                    ->placeholder('e.g., Minimum of 14 units of Core Courses')
                                    ->required()
                            )
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (array $data): void {
                $minUnits = [
                    'Core' => (int)($data['min_units_Core'] ?? 0),
                    'Prescribed' => (int)($data['min_units_Prescribed'] ?? 0),
                    'Major' => (int)($data['min_units_Major'] ?? 0),
                    'Specialization' => (int)($data['min_units_Specialization'] ?? 0),
                    'Cognate' => (int)($data['min_units_Cognate'] ?? 0),
                    'Elective' => (int)($data['min_units_Elective'] ?? 0),
                    'Seminar' => (int)($data['min_units_Seminar'] ?? 0),
                    'Thesis' => (int)($data['min_units_Thesis'] ?? 0),
                    'Dissertation' => (int)($data['min_units_Dissertation'] ?? 0),
                    'Field Study' => (int)($data['min_units_FieldStudy'] ?? 0),
                ];
                $minUnits = array_filter($minUnits, fn($v) => $v > 0);

                $totalUnitsRequired = !empty($data['has_custom_total'])
                    ? (int)($data['total_units_required'] ?? 0)
                    : array_sum($minUnits);

                $program = Program::create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'degree_level' => $data['degree_level'],
                    'is_active' => $data['is_active'],
                    'max_residency_years' => $data['max_residency_years'] ? (int)$data['max_residency_years'] : null,
                    'description' => $data['description'] ?? null,
                    'min_units_per_type' => $minUnits,
                    'total_units_required' => $totalUnitsRequired,
                ]);

                if (!empty($data['majors'])) {
                    foreach ($data['majors'] as $majorData) {
                        $program->majors()->create([
                            'name' => $majorData['name'],
                            'description' => $majorData['description'] ?? null,
                        ]);
                    }
                }

                if (!empty($data['requirements'])) {
                    foreach ($data['requirements'] as $index => $req) {
                        $text = is_array($req) ? ($req['requirement_text'] ?? '') : $req;
                        if (filled($text)) {
                            $program->requirements()->create([
                                'requirement_text' => $text,
                                'sort_order' => $index,
                            ]);
                        }
                    }
                }

                $this->selectedProgramId = $program->id;
                $this->resetComputedCache();
                $this->dispatch('program-switched');

                Notification::make()
                    ->success()
                    ->title('Program Created')
                    ->body("Successfully created {$program->code}.")
                    ->send();
            });
    }

    public function editProgramSettingsAction(): Action
    {
        return Action::make('editProgramSettings')
            ->label('Edit Program Settings')
            ->modalHeading('Program Settings')
            ->modalWidth('xl')
            ->record(fn () => $this->getSelectedProgram())
            ->fillForm(function () {
                $program = $this->getSelectedProgram();
                if (! $program) return [];

                $minUnits = $program->min_units_per_type ?? [];
                
                $majors = $program->majors->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'description' => $m->description,
                ])->toArray();

                $requirements = $program->requirements->sortBy('sort_order')->map(fn($r) => $r->requirement_text)->toArray();

                $minUnitsSum = array_sum([
                    (int)($minUnits['Core'] ?? 0),
                    (int)($minUnits['Prescribed'] ?? 0),
                    (int)($minUnits['Major'] ?? 0),
                    (int)($minUnits['Specialization'] ?? 0),
                    (int)($minUnits['Cognate'] ?? 0),
                    (int)($minUnits['Elective'] ?? 0),
                    (int)($minUnits['Seminar'] ?? 0),
                    (int)($minUnits['Thesis'] ?? 0),
                    (int)($minUnits['Dissertation'] ?? 0),
                    (int)($minUnits['Field Study'] ?? 0),
                ]);

                $hasCustomTotal = $program->total_units_required !== $minUnitsSum;

                return [
                    'code' => $program->code,
                    'name' => $program->name,
                    'degree_level' => $program->degree_level?->value,
                    'is_active' => $program->is_active,
                    'max_residency_years' => $program->max_residency_years,
                    'description' => $program->description,
                    'has_custom_total' => $hasCustomTotal,
                    'total_units_required' => $program->total_units_required,
                    'min_units_Core' => $minUnits['Core'] ?? null,
                    'min_units_Prescribed' => $minUnits['Prescribed'] ?? null,
                    'min_units_Major' => $minUnits['Major'] ?? null,
                    'min_units_Specialization' => $minUnits['Specialization'] ?? null,
                    'min_units_Cognate' => $minUnits['Cognate'] ?? null,
                    'min_units_Elective' => $minUnits['Elective'] ?? null,
                    'min_units_Seminar' => $minUnits['Seminar'] ?? null,
                    'min_units_Thesis' => $minUnits['Thesis'] ?? null,
                    'min_units_Dissertation' => $minUnits['Dissertation'] ?? null,
                    'min_units_FieldStudy' => $minUnits['Field Study'] ?? null,
                    'majors' => $majors,
                    'requirements' => $requirements,
                ];
            })
            ->form([
                Section::make('Program Details')
                    ->collapsible()
                    ->columns(6)
                    ->schema([
                        TextInput::make('code')
                            ->label('Program Code')
                            ->required()
                            ->maxLength(20)
                            ->unique(table: 'programs', column: 'code', ignoreRecord: true)
                            ->placeholder('e.g., PhD-DVST')
                            ->columnSpan(2),
                        Select::make('degree_level')
                            ->label('Degree Level')
                            ->options(DegreeLevel::class)
                            ->required()
                            ->columnSpan(2),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                        TextInput::make('name')
                            ->label('Program Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Doctor of Philosophy in Development Studies')
                            ->columnSpan(6),
                        TextInput::make('max_residency_years')
                            ->label('Max Residency (years)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('e.g., 5')
                            ->columnSpan(3),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpan(6),
                    ]),
                Section::make('Specializations / Majors')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('majors')
                            ->hiddenLabel()
                            ->grid(2)
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('name')
                                    ->label('Specialization Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Local Governance and Development'),
                                TextInput::make('description')
                                    ->label('Description')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Optional description'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Minimum Units Required per Course Type')
                    ->description('Set the minimum required units for each course type.')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('min_units_Core')->label('Core')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Prescribed')->label('Prescribed')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Major')->label('Major')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Specialization')->label('Specialization')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Cognate')->label('Cognate')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Elective')->label('Elective')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Seminar')->label('Seminar')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Thesis')->label('Thesis')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_Dissertation')->label('Dissertation')->numeric()->placeholder('e.g., 0'),
                        TextInput::make('min_units_FieldStudy')->label('Field Study')->numeric()->placeholder('e.g., 0'),
                        Toggle::make('has_custom_total')
                            ->label('Set Total Units Manually')
                            ->live()
                            ->columnSpan(2),
                        TextInput::make('total_units_required')
                            ->label('Total Units Required')
                            ->numeric()
                            ->required(fn ($get) => $get('has_custom_total'))
                            ->visible(fn ($get) => $get('has_custom_total'))
                            ->columnSpan(2),
                    ]),
                Section::make('Requirements')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('requirements')
                            ->hiddenLabel()
                            ->simple(
                                TextInput::make('requirement_text')
                                    ->placeholder('e.g., Minimum of 14 units of Core Courses')
                                    ->required()
                            )
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (array $data): void {
                $program = $this->getSelectedProgram();
                if (! $program) return;

                $minUnits = [
                    'Core' => (int)($data['min_units_Core'] ?? 0),
                    'Prescribed' => (int)($data['min_units_Prescribed'] ?? 0),
                    'Major' => (int)($data['min_units_Major'] ?? 0),
                    'Specialization' => (int)($data['min_units_Specialization'] ?? 0),
                    'Cognate' => (int)($data['min_units_Cognate'] ?? 0),
                    'Elective' => (int)($data['min_units_Elective'] ?? 0),
                    'Seminar' => (int)($data['min_units_Seminar'] ?? 0),
                    'Thesis' => (int)($data['min_units_Thesis'] ?? 0),
                    'Dissertation' => (int)($data['min_units_Dissertation'] ?? 0),
                    'Field Study' => (int)($data['min_units_FieldStudy'] ?? 0),
                ];
                $minUnits = array_filter($minUnits, fn($v) => $v > 0);

                $totalUnitsRequired = !empty($data['has_custom_total'])
                    ? (int)($data['total_units_required'] ?? 0)
                    : array_sum($minUnits);

                $program->update([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'degree_level' => $data['degree_level'],
                    'is_active' => $data['is_active'],
                    'max_residency_years' => $data['max_residency_years'] ? (int)$data['max_residency_years'] : null,
                    'description' => $data['description'] ?? null,
                    'min_units_per_type' => $minUnits,
                    'total_units_required' => $totalUnitsRequired,
                ]);

                // Sync Majors (preserve IDs)
                $majorsData = $data['majors'] ?? [];
                $keepIds = [];
                foreach ($majorsData as $majorData) {
                    if (!empty($majorData['id'])) {
                        $major = $program->majors()->find($majorData['id']);
                        if ($major) {
                            $major->update([
                                'name' => $majorData['name'],
                                'description' => $majorData['description'] ?? null,
                            ]);
                            $keepIds[] = $major->id;
                        }
                    } else {
                        $newMajor = $program->majors()->create([
                            'name' => $majorData['name'],
                            'description' => $majorData['description'] ?? null,
                        ]);
                        $keepIds[] = $newMajor->id;
                    }
                }
                $program->majors()->whereNotIn('id', $keepIds)->delete();

                // Recreate requirements
                $program->requirements()->delete();
                if (!empty($data['requirements'])) {
                    foreach ($data['requirements'] as $index => $req) {
                        $text = is_array($req) ? ($req['requirement_text'] ?? '') : $req;
                        if (filled($text)) {
                            $program->requirements()->create([
                                'requirement_text' => $text,
                                'sort_order' => $index,
                            ]);
                        }
                    }
                }

                $this->resetComputedCache();
                $this->dispatch('curriculum-updated');

                Notification::make()
                    ->success()
                    ->title('Program Settings Updated')
                    ->body("Successfully updated settings for {$program->code}.")
                    ->send();
            });
    }

    public function addCourseMappingAction(): Action
    {
        return Action::make('addCourseMapping')
            ->label('Add Course')
            ->modalHeading('Add Course to Curriculum')
            ->modalWidth('2xl')
            ->fillForm(fn () => [
                'course_type' => $this->addCourseDefaultType,
                'is_required' => true,
            ])
            ->form([
                Select::make('course_id')
                    ->label('Course')
                    ->options(fn () => $this->getAvailableCourses()->pluck('label', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('course_type')
                    ->label('Course Type')
                    ->options(CourseType::class)
                    ->required()
                    ->live(),
                Select::make('program_major_id')
                    ->label('Specialization / Major')
                    ->options(fn () => ProgramMajor::where('program_id', $this->selectedProgramId)->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload(),
                Select::make('cognate_field_id')
                    ->label('Cognate Field')
                    ->options(fn () => CognateField::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->hidden(fn ($get) => ($get('course_type') instanceof CourseType ? $get('course_type')->value : $get('course_type')) !== 'cognate')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                    ])
                    ->createOptionUsing(fn (array $data) => CognateField::create($data)->id),
                Select::make('semester_offered')
                    ->label('Semester Offered')
                    ->options([
                        'First Semester' => 'First Semester',
                        'Second Semester' => 'Second Semester',
                        'First and Second Semester' => 'First and Second Semester',
                        'Midyear' => 'Midyear',
                        'First Semester, Second Semester, and Midyear' => 'First Semester, Second Semester, and Midyear',
                    ])
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('units')
                    ->label('Units Override')
                    ->numeric()
                    ->minValue(0)
                    ->placeholder('Leave blank to use course default units'),
                Toggle::make('is_required')
                    ->label('Required')
                    ->default(true),
                TextInput::make('prerequisite_text')
                    ->label('Prerequisites')
                    ->maxLength(500),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                if (! $this->selectedProgramId) return;

                // Check if already mapped
                $exists = ProgramCourse::where('program_id', $this->selectedProgramId)
                    ->where('course_id', $data['course_id'])
                    ->where('course_type', $data['course_type'])
                    ->when(!empty($data['program_major_id']), fn($q) => $q->where('program_major_id', $data['program_major_id']))
                    ->exists();

                if ($exists) {
                    Notification::make()
                        ->warning()
                        ->title('Course Already Mapped')
                        ->body('This course is already mapped to the selected program with these settings.')
                        ->send();
                    return;
                }

                $wasEmpty = ProgramCourse::where('program_id', $this->selectedProgramId)->count() === 0;

                ProgramCourse::create([
                    'program_id' => $this->selectedProgramId,
                    'course_id' => $data['course_id'],
                    'course_type' => $data['course_type'],
                    'program_major_id' => $data['program_major_id'] ?? null,
                    'cognate_field_id' => $data['cognate_field_id'] ?? null,
                    'units' => $data['units'] ? (int)$data['units'] : null,
                    'semester_offered' => $data['semester_offered'] ?? null,
                    'is_required' => (bool)($data['is_required'] ?? true),
                    'prerequisite_text' => $data['prerequisite_text'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->resetComputedCache();
                $this->dispatch('curriculum-updated');

                Notification::make()
                    ->success()
                    ->title('Course Added')
                    ->body('Successfully mapped course to the curriculum.')
                    ->send();

                if ($wasEmpty) {
                    $this->js('setTimeout(() => window.location.href = "' . static::getUrl(['program' => $this->selectedProgramId]) . '", 100)');
                }
            });
    }

    public function editCourseMappingAction(): Action
    {
        return Action::make('editCourseMapping')
            ->label('Edit Course Mapping')
            ->modalHeading('Edit Course Mapping')
            ->modalWidth('2xl')
            ->record(fn () => ProgramCourse::find($this->editingMappingId))
            ->fillForm(function (ProgramCourse $record) {
                return [
                    'course_id' => $record->course_id,
                    'course_type' => $record->course_type?->value,
                    'program_major_id' => $record->program_major_id,
                    'cognate_field_id' => $record->cognate_field_id,
                    'units' => $record->units,
                    'semester_offered' => $record->semester_offered,
                    'is_required' => $record->is_required,
                    'prerequisite_text' => $record->prerequisite_text,
                    'notes' => $record->notes,
                ];
            })
            ->form([
                Select::make('course_id')
                    ->label('Course')
                    ->options(fn (ProgramCourse $record) => $record->course ? [$record->course->id => "{$record->course->course_code} — {$record->course->course_name}"] : [])
                    ->disabled()
                    ->required(),
                Select::make('course_type')
                    ->label('Course Type')
                    ->options(CourseType::class)
                    ->required()
                    ->live(),
                Select::make('program_major_id')
                    ->label('Specialization / Major')
                    ->options(fn () => ProgramMajor::where('program_id', $this->selectedProgramId)->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload(),
                Select::make('cognate_field_id')
                    ->label('Cognate Field')
                    ->options(fn () => CognateField::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->hidden(fn ($get) => ($get('course_type') instanceof CourseType ? $get('course_type')->value : $get('course_type')) !== 'cognate')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                    ])
                    ->createOptionUsing(fn (array $data) => CognateField::create($data)->id),
                Select::make('semester_offered')
                    ->label('Semester Offered')
                    ->options([
                        'First Semester' => 'First Semester',
                        'Second Semester' => 'Second Semester',
                        'First and Second Semester' => 'First and Second Semester',
                        'Midyear' => 'Midyear',
                        'First Semester, Second Semester, and Midyear' => 'First Semester, Second Semester, and Midyear',
                    ])
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('units')
                    ->label('Units Override')
                    ->numeric()
                    ->minValue(0)
                    ->placeholder('Leave blank to use course default units'),
                Toggle::make('is_required')
                    ->label('Required')
                    ->default(true),
                TextInput::make('prerequisite_text')
                    ->label('Prerequisites')
                    ->maxLength(500),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, ProgramCourse $record): void {
                $record->update([
                    'course_type' => $data['course_type'],
                    'program_major_id' => $data['program_major_id'] ?? null,
                    'cognate_field_id' => $data['cognate_field_id'] ?? null,
                    'units' => $data['units'] ? (int)$data['units'] : null,
                    'semester_offered' => $data['semester_offered'] ?? null,
                    'is_required' => (bool)($data['is_required'] ?? true),
                    'prerequisite_text' => $data['prerequisite_text'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->resetComputedCache();
                $this->dispatch('curriculum-updated');

                Notification::make()
                    ->success()
                    ->title('Mapping Updated')
                    ->body('Successfully updated course mapping.')
                    ->send();
            });
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
        $this->dispatch('curriculum-updated');
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
        $this->dispatch('curriculum-updated');
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
