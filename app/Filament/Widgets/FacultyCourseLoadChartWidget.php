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

class FacultyCourseLoadChartWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    // Do not auto-discover in dashboard
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.faculty-course-load-chart-widget';

    protected int | string | array $columnSpan = 'full';

    // Local form filters
    public ?int $fromSemesterId = null;
    public ?int $toSemesterId = null;
    public ?string $sortBy = 'name_asc';
    public array $chartData = [];

    public function mount(): void
    {
        $semesters = Semester::orderByRaw('CAST(term_code AS UNSIGNED) ASC')->get();
        if ($semesters->isNotEmpty()) {
            $this->fromSemesterId = $semesters->first()->id;
            $this->toSemesterId = $semesters->last()->id;
        }
        $this->recomputeData();
    }

    public function rendering(): void
    {
        $this->recomputeData();
    }

    public function updatedFromSemesterId(): void
    {
        $this->recomputeData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-course-load-chart"))');
    }

    public function updatedToSemesterId(): void
    {
        $this->recomputeData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-course-load-chart"))');
    }

    public function updatedSortBy(): void
    {
        $this->recomputeData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-course-load-chart"))');
    }

    #[On('workload-changed')]
    public function refreshData(): void
    {
        $this->recomputeData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-course-load-chart"))');
    }

    protected function recomputeData(): void
    {
        $fromSem = Semester::find($this->fromSemesterId);
        $toSem = Semester::find($this->toSemesterId);

        $activeSemesterIds = [];
        if ($fromSem && $toSem) {
            $fromCode = (int) $fromSem->term_code;
            $toCode = (int) $toSem->term_code;
            $minCode = min($fromCode, $toCode);
            $maxCode = max($fromCode, $toCode);

            $activeSemesterIds = Semester::whereRaw('CAST(term_code AS UNSIGNED) BETWEEN ? AND ?', [$minCode, $maxCode])
                ->pluck('id')
                ->toArray();
        }

        $query = Faculty::where('is_external', false)
            ->whereNull('deleted_at');

        if (!empty($activeSemesterIds)) {
            $query->withCount(['courseOfferings' => function ($q) use ($activeSemesterIds) {
                $q->whereIn('semester_id', $activeSemesterIds);
            }]);
        } else {
            $query->withCount('courseOfferings');
        }

        $faculties = $query->get()->filter(fn ($f) => $f->course_offerings_count > 0);

        // Sorting
        $sortBy = $this->sortBy ?? 'name_asc';
        if ($sortBy === 'name_asc') {
            $faculties = $faculties->sortBy([
                ['last_name', 'asc'],
                ['first_name', 'asc']
            ]);
        } elseif ($sortBy === 'name_desc') {
            $faculties = $faculties->sortBy([
                ['last_name', 'desc'],
                ['first_name', 'desc']
            ]);
        } elseif ($sortBy === 'load_desc') {
            $faculties = $faculties->sortByDesc('course_offerings_count');
        } elseif ($sortBy === 'load_asc') {
            $faculties = $faculties->sortBy('course_offerings_count');
        }

        $faculties = $faculties->values();

        $chartLabels = [];
        $chartData = [];

        foreach ($faculties as $f) {
            $formattedName = $f->last_name . ', ' . substr($f->first_name, 0, 1) . '.';
            $chartLabels[] = $formattedName;
            $chartData[] = (int) $f->course_offerings_count;
        }

        $this->chartData = [
            'labels' => $chartLabels,
            'courses' => $chartData,
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('fromSemesterId')
                    ->options($this->terms)
                    ->label('From Term')
                    ->placeholder('Select start term')
                    ->live()
                    ->extraAttributes(['class' => 'w-[200px]']),

                Select::make('toSemesterId')
                    ->options($this->terms)
                    ->label('To Term')
                    ->placeholder('Select end term')
                    ->live()
                    ->extraAttributes(['class' => 'w-[200px]']),

                Select::make('sortBy')
                    ->options([
                        'name_asc' => '🔤 Sort by Name (A → Z)',
                        'name_desc' => '🔤 Sort by Name (Z → A)',
                        'load_desc' => '📊 Sort by Courses (High → Low)',
                        'load_asc' => '📊 Sort by Courses (Low → High)',
                    ])
                    ->label('Sort by')
                    ->selectablePlaceholder(false)
                    ->live()
                    ->extraAttributes(['class' => 'w-[220px]'])
            ])
            ->columns(3);
    }

    public function getTermsProperty(): array
    {
        return Semester::with('academicYear')
            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
            ->toArray();
    }
}
