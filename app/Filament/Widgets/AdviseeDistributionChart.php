<?php

namespace App\Filament\Widgets;

use App\Models\Faculty;
use App\Models\Semester;
use Filament\Widgets\Widget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class AdviseeDistributionChart extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.advisee-distribution-chart';
    protected int | string | array $columnSpan = 'full';

    // Reactive properties passed from page-level filters
    public ?array $semesterIds = null;

    // Local form filter properties
    public ?array $termFilter = [];
    public ?string $sortBy = 'name_asc';
    public array $chartData = [];

    protected bool $isInitialized = false;

    public function mount(): void
    {
        $this->termFilter = $this->semesterIds ?? [];
        $this->chartData = $this->getChartData();
    }

    public function rendering(): void
    {
        $this->chartData = $this->getChartData();
    }

    public function updatedSemesterIds(): void
    {
        if (!$this->isInitialized && !empty($this->semesterIds)) {
            $this->termFilter = $this->semesterIds;
            $this->isInitialized = true;
        }
        $this->chartData = $this->getChartData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-advisee-chart"))');
    }

    public function updatedTermFilter(): void
    {
        $this->chartData = $this->getChartData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-advisee-chart"))');
    }

    public function updatedSortBy(): void
    {
        $this->chartData = $this->getChartData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-advisee-chart"))');
    }

    #[On('assignment-changed')]
    public function refreshData(): void
    {
        $this->chartData = $this->getChartData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-advisee-chart"))');
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('termFilter')
                    ->multiple()
                    ->options($this->terms)
                    ->label('Term filter')
                    ->placeholder('Filter by Terms (All)')
                    ->live()
                    ->extraAttributes(['class' => 'min-w-[220px]']),

                Select::make('sortBy')
                    ->options([
                        'name_asc' => '🔤 Sort by Name (A → Z)',
                        'name_desc' => '🔤 Sort by Name (Z → A)',
                        'total' => '📊 Sort by Total Load',
                        'student' => '📊 Sort by Student Advisory',
                        'graduate' => '📊 Sort by Graduate Committee',
                        'academic' => '📊 Sort by Academic Output',
                    ])
                    ->label('Sort by')
                    ->selectablePlaceholder(false)
                    ->live()
                    ->extraAttributes(['class' => 'w-[220px]'])
            ])
            ->columns(2);
    }

    public function getTermsProperty(): array
    {
        return Semester::with('academicYear')
            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
            ->toArray();
    }

    public function getChartData(): array
    {
        $scmTermCond = '';
        $gcmTermCond = '';
        $aocTermCond = '';
        $sortColumn = 'total_count';
        $sortDirection = 'DESC';
        $sortByName = false;

        // Use widget's own term filter first, fallback to parent page semester filters
        $activeTerms = !empty($this->termFilter) ? $this->termFilter : ($this->semesterIds ?? []);

        if (!empty($activeTerms)) {
            $ids = implode(',', array_map('intval', $activeTerms));
            $scmTermCond = " AND (student_committee_members.term_start_id IN ({$ids}) OR student_committee_members.term_end_id IN ({$ids}))";
            $gcmTermCond = " AND (graduate_committee_members.term_start_id IN ({$ids}) OR graduate_committee_members.term_end_id IN ({$ids}))";
            $aocTermCond = " AND (academic_output_committee.term_start_id IN ({$ids}) OR academic_output_committee.term_end_id IN ({$ids}))";
        }

        $filter = $this->sortBy ?? 'name_asc';

        match ($filter) {
            'student' => $sortColumn = 'student_count',
            'graduate' => $sortColumn = 'grad_count',
            'academic' => $sortColumn = 'ao_count',
            'total' => $sortColumn = 'total_count',
            'name_asc' => $sortByName = true,
            'name_desc' => ($sortByName = true) && ($sortDirection = 'DESC'),
            default => $sortByName = true,
        };
        if ($filter === 'name_asc') $sortDirection = 'ASC';

        $query = Faculty::where('is_external', false)
            ->whereNull('deleted_at')
            ->addSelect([
                'faculty.*',
                DB::raw("(SELECT COUNT(*) FROM student_committee_members WHERE student_committee_members.faculty_id = faculty.id{$scmTermCond}) as student_count"),
                DB::raw("(SELECT COUNT(*) FROM graduate_committee_members WHERE graduate_committee_members.faculty_id = faculty.id{$gcmTermCond}) as grad_count"),
                DB::raw("(SELECT COUNT(*) FROM academic_output_committee WHERE academic_output_committee.faculty_id = faculty.id{$aocTermCond}) as ao_count"),
                DB::raw("(
                    (SELECT COUNT(*) FROM student_committee_members WHERE student_committee_members.faculty_id = faculty.id{$scmTermCond}) +
                    (SELECT COUNT(*) FROM graduate_committee_members WHERE graduate_committee_members.faculty_id = faculty.id{$gcmTermCond}) +
                    (SELECT COUNT(*) FROM academic_output_committee WHERE academic_output_committee.faculty_id = faculty.id{$aocTermCond})
                ) as total_count"),
            ]);

        if ($sortByName) {
            $query->orderBy('last_name', $sortDirection)->orderBy('first_name', $sortDirection);
        } else {
            $query->orderByDesc($sortColumn);
        }

        $faculty = $query->get();

        $labels = $faculty->map(fn ($f) => $f->last_name . ', ' . substr($f->first_name, 0, 1) . '.')->values()->toArray();
        $studentData = $faculty->pluck('student_count')->map(fn ($v) => (int) $v)->values()->toArray();
        $gradData = $faculty->pluck('grad_count')->map(fn ($v) => (int) $v)->values()->toArray();
        $aoData = $faculty->pluck('ao_count')->map(fn ($v) => (int) $v)->values()->toArray();

        return [
            'labels' => $labels,
            'studentData' => $studentData,
            'gradData' => $gradData,
            'aoData' => $aoData,
        ];
    }
}
