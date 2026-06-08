<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Enrollee;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Widget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Livewire\Attributes\On;

class StudentOverviewWidget extends Widget implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.widgets.student-overview-widget';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public ?array $termFilter = [];
    public array $chartData = [];

    public function mount(): void
    {
        $this->chartData = $this->getOverviewData();
    }

    public function rendering(): void
    {
        $this->chartData = $this->getOverviewData();
    }

    public function updatedTermFilter(): void
    {
        $this->dispatch('studentTermFilterUpdated', termFilter: $this->termFilter);
    }

    #[On('studentTermFilterUpdated')]
    public function handleTermFilterUpdated(array $termFilter): void
    {
        if ($this->termFilter !== $termFilter) {
            $this->termFilter = $termFilter;
            $this->form->fill(['termFilter' => $termFilter]);
            $this->chartData = $this->getOverviewData();
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('termFilter')
                    ->multiple()
                    ->options($this->terms)
                    ->label('')
                    ->placeholder('Filter by Terms (All)')
                    ->live()
                    ->extraAttributes(['class' => 'min-w-[200px]'])
            ]);
    }

    public function getTermsProperty(): array
    {
        return \App\Models\Semester::with('academicYear')
            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
            ->get()
            ->mapWithKeys(fn($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
            ->toArray();
    }

    public function getOverviewData(): array
    {
        $termFilter = $this->termFilter;

        // Core Metrics from Students Table
        $studentQuery = Student::query();
        if (!empty($termFilter)) {
            $studentQuery->whereHas('enrollees', function ($q) use ($termFilter) {
                $q->whereIn('term_id', $termFilter);
            });
        }

        $totalStudents = (clone $studentQuery)->count();
        $activeStudents = (clone $studentQuery)->where('student_status', 'active')->count();

        // Count graduated students from the graduates table (linked by student_number)
        // This avoids undercounting students who graduated from one program but re-enrolled in another
        // (which resets their student_status back to 'active').
        $graduatedQuery = \App\Models\Graduate::whereNotNull('student_number')
            ->where('student_number', '!=', '');
        if (!empty($termFilter)) {
            $graduatedQuery->whereIn('student_number', function ($q) use ($termFilter) {
                $q->select('student_number')->from('students')
                    ->whereIn('student_number', function ($eq) use ($termFilter) {
                        $eq->select('student_number')->from('enrollees')
                            ->whereIn('term_id', $termFilter);
                    });
            });
        }
        $graduatedStudents = $graduatedQuery->distinct('student_number')->count('student_number');

        $onLeaveStudents = (clone $studentQuery)->whereIn('student_status', ['on-leave', 'on leave'])->count();
        $inactiveStudents = (clone $studentQuery)->where('student_status', 'inactive')->count();

        // Demographics from Enrollee Table (since it has better populated fields right now)
        $avgTermsQuery = DB::table('enrollees')
            ->select('student_number', DB::raw('COUNT(DISTINCT term_id) as term_count'));

        if (!empty($termFilter)) {
            $avgTermsQuery->whereIn('student_number', function ($q) use ($termFilter) {
                $q->select('student_number')->from('enrollees')->whereIn('term_id', $termFilter);
            });
        }

        $avgTermsRaw = $avgTermsQuery->groupBy('student_number')->get();
        $averageTerms = $avgTermsRaw->count() > 0 ? round($avgTermsRaw->avg('term_count'), 1) : 0;

        // Average Age
        $agesQuery = DB::table('enrollees')
            ->select('student_number', DB::raw('MAX(birthdate) as bdate'))
            ->whereNotNull('birthdate')
            ->where('birthdate', 'not like', '%0000%');

        if (!empty($termFilter)) {
            $agesQuery->whereIn('student_number', function ($q) use ($termFilter) {
                $q->select('student_number')->from('enrollees')->whereIn('term_id', $termFilter);
            });
        }

        $ages = $agesQuery->groupBy('student_number')
            ->get()
            ->map(function ($record) {
                try {
                    return \Carbon\Carbon::parse($record->bdate)->age;
                } catch (\Exception $e) {
                    return null;
                }
            })->filter();
        $averageAge = $ages->count() > 0 ? round($ages->avg()) : 0;

        // Program Distribution for Chart
        $programsQuery = Student::join('programs', 'students.program_id', '=', 'programs.id')
            ->select('programs.code', DB::raw('count(*) as count'));

        if (!empty($termFilter)) {
            $programsQuery->whereHas('enrollees', function ($q) use ($termFilter) {
                $q->whereIn('term_id', $termFilter);
            });
        }

        $programsCount = $programsQuery->groupBy('programs.code')
            ->orderByDesc('count')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        // Generate distinct colors
        $palette = ['#1A5C38', '#1A2B6B', '#28A745', '#0284c7', '#8b5cf6', '#eab308', '#ef4444', '#14b8a6', '#f97316', '#6366f1'];

        foreach ($programsCount as $index => $p) {
            $labels[] = $p->code;
            $data[] = $p->count;
            $colors[] = $palette[$index % count($palette)];
        }

        // Top Programs
        $topPrograms = $programsCount->take(4)->map(function ($p) use ($termFilter) {
            $avgUnitsQuery = Student::join('programs', 'students.program_id', '=', 'programs.id')
                ->where('programs.code', $p->code);

            if (!empty($termFilter)) {
                $avgUnitsQuery->whereHas('enrollees', function ($q) use ($termFilter) {
                    $q->whereIn('term_id', $termFilter);
                });
            }

            $avgUnits = $avgUnitsQuery->avg('total_units_earned') ?? 0;

            return [
                'code' => $p->code,
                'count' => $p->count,
                'avg_units' => round($avgUnits, 1)
            ];
        });

        return [
            'total' => $totalStudents,
            'active' => $activeStudents,
            'graduated' => $graduatedStudents,
            'onLeave' => $onLeaveStudents,
            'inactive' => $inactiveStudents,
            'averageAge' => $averageAge,
            'averageTerms' => $averageTerms,
            'chart' => [
                'labels' => $labels,
                'data' => $data,
                'colors' => $colors,
            ],
            'topPrograms' => $topPrograms->toArray()
        ];
    }
}
