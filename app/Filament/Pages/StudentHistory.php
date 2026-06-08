<?php

namespace App\Filament\Pages;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use App\Filament\Pages\StudentHistory\HasAcademicOutputManagement;
use App\Filament\Pages\StudentHistory\HasAcademicProgress;
use App\Filament\Pages\StudentHistory\HasEnrollmentManagement;
use App\Filament\Pages\StudentHistory\HasExportActions;
use App\Filament\Pages\StudentHistory\HasFormHelpers;
use App\Filament\Pages\StudentHistory\HasGraduationManagement;
use App\Filament\Pages\StudentHistory\HasMilestoneManagement;
use App\Filament\Pages\StudentHistory\HasStudentActions;
use App\Filament\Pages\StudentHistory\HasStudentSearch;
use App\Filament\Pages\StudentHistory\HasStudentTable;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class StudentHistory extends Page implements HasTable
{
    use HasPageShield;
    use HasFiltersForm;
    use InteractsWithTable {
        InteractsWithTable::normalizeTableFilterValuesFromQueryString insteadof HasFiltersForm;
    }
    use HasStudentTable {
        HasStudentTable::table insteadof InteractsWithTable;
    }

    // ── Extracted Traits ──
    use HasFormHelpers;
    use HasStudentSearch;
    use HasStudentActions;
    use HasExportActions;
    use HasGraduationManagement;
    use HasAcademicProgress;
    use HasEnrollmentManagement;
    use HasMilestoneManagement;
    use HasAcademicOutputManagement;

    // ── Page Configuration ──
    protected static string | \UnitEnum | null $navigationGroup = 'Student Management';
    protected static ?string $navigationLabel = 'List of Students';
    protected static ?string $breadcrumb = 'List of Students';
    protected static ?int $navigationSort = 4;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $slug = 'list-of-students';
    protected string $view = 'filament.pages.student-history';

    // ── Component State ──
    public ?string $studentNumber = '';
    public ?array $studentInfo = null;
    // $filters is now managed by HasFiltersForm trait
    // (provides #[Url] persistence, session persistence, and query string normalization)
    public array $searchSuggestions = [];
    public bool $showSuggestions = false;
    public bool $revealGrades = false;

    // ── Milestone CRUD State ──
    public bool $showMilestoneForm = false;
    public ?int $deletingMilestoneId = null;
    public string $newMilestoneName = '';
    public string $newMilestoneCategory = 'other';

    // ── Academic Output CRUD State ──
    public bool $showAoForm = false;
    public ?int $editingAoId = null;
    public ?int $deletingAoId = null;
    public string $aoTitle = '';
    public string $aoType = 'thesis';
    public string $aoStatus = 'topic-approved';
    public ?string $aoProposalDefenseDate = null;
    public ?string $aoProposalDefenseResult = null;
    public ?string $aoFinalDefenseDate = null;
    public ?string $aoFinalDefenseResult = null;

    // ── Academic Output Committee State ──
    public string $aoAdviser = '';
    public string $aoCoAdviser = '';
    public string $aoChair = '';
    public string $aoCoChair = '';
    public string $aoMember1 = '';
    public string $aoMember2 = '';
    public string $aoMember3 = '';

    // ── Enrollment CRUD State ──
    public ?int $deletingEnrollmentId = null;

    // ── Graduation CRUD State ──
    public ?int $editingGraduateId = null;

    public function mount(): void
    {
        $this->studentNumber = request()->query('studentNumber', '');
        if (!empty($this->studentNumber)) {
            $this->search();
        }
    }

    public function toggleGradeVisibility(): void
    {
        if (auth()->user()?->hasRole('super_admin')) {
            $this->revealGrades = !$this->revealGrades;
        }
    }

    // getForms() removed — HasFiltersForm trait handles form registration via bootedHasFiltersForm()

    public function filtersForm(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        $termCodes = \App\Models\Enrollee::select('term_id')
            ->distinct()
            ->orderBy('term_id')
            ->pluck('term_id')
            ->toArray();

        $semesters = \App\Models\Semester::whereIn('term_code', $termCodes)
            ->with('academicYear')
            ->get()
            ->keyBy('term_code');

        $options = [];
        foreach ($termCodes as $code) {
            $semester = $semesters->get($code);
            $label = $semester ? "[{$code}] {$semester->label}" : "[{$code}]";
            $options[$code] = $label;
        }

        return $form
            ->schema([
                \Filament\Forms\Components\Select::make('filterFrom')
                    ->label('From Term')
                    ->options($options)
                    ->placeholder('All Terms')
                    ->live(),
                \Filament\Forms\Components\Select::make('filterTo')
                    ->label('To Term')
                    ->options($options)
                    ->placeholder('All Terms')
                    ->live(),
            ])
            ->statePath('filters')
            ->columns([
                'default' => 1,
                'sm' => 2,
                'md' => 2,
                'lg' => 2,
                'xl' => 2,
                '2xl' => 2,
            ]);
    }
}
