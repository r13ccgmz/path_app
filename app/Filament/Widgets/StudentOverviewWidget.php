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

class StudentOverviewWidget extends Widget implements HasForms
{
    use InteractsWithForms;
    protected string $view = 'filament.widgets.student-overview-widget';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public ?array $termFilter = [];

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
        $cacheKey = 'student_overview_data_' . (empty($this->termFilter) ? 'all' : implode('_', $this->termFilter));

        // Cache the entire overview for 5 minutes to prevent heavy DB load
        return cache()->remember($cacheKey, 300, function () {
            // Core Metrics from Students Table
            $studentQuery = Student::query();
            if (!empty($this->termFilter)) {
                $studentQuery->whereHas('enrollees', function ($q) {
                    $q->whereIn('term_id', $this->termFilter);
                });
            }

            $totalStudents = (clone $studentQuery)->count();
            $activeStudents = (clone $studentQuery)->where('student_status', 'active')->count();
            $graduatedStudents = (clone $studentQuery)->where('student_status', 'graduated')->count();
            $onLeaveStudents = (clone $studentQuery)->whereIn('student_status', ['on-leave', 'on leave'])->count();

            // Demographics from Enrollee Table (since it has better populated fields right now)
            $avgTermsQuery = DB::table('enrollees')
                ->select('student_number', DB::raw('COUNT(DISTINCT term_id) as term_count'));

            if (!empty($this->termFilter)) {
                // If filtering by term, we only consider students enrolled in this term
                // but we still want their *overall* average terms? Or just for this term?
                // The user probably still wants the overall average of students enrolled in this term.
                $avgTermsQuery->whereIn('student_number', function ($q) {
                    $q->select('student_number')->from('enrollees')->whereIn('term_id', $this->termFilter);
                });
            }

            $avgTermsRaw = $avgTermsQuery->groupBy('student_number')->get();
            $averageTerms = $avgTermsRaw->count() > 0 ? round($avgTermsRaw->avg('term_count'), 1) : 0;

            // Average Age
            // We get birthdates that are valid dates and compute age
            $agesQuery = DB::table('enrollees')
                ->select('student_number', DB::raw('MAX(birthdate) as bdate'))
                ->whereNotNull('birthdate')
                ->where('birthdate', 'not like', '%0000%');

            if (!empty($this->termFilter)) {
                $agesQuery->whereIn('student_number', function ($q) {
                    $q->select('student_number')->from('enrollees')->whereIn('term_id', $this->termFilter);
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

            if (!empty($this->termFilter)) {
                $programsQuery->whereHas('enrollees', function ($q) {
                    $q->whereIn('term_id', $this->termFilter);
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

            // Top Programs (Themes & Initiatives equivalent)
            $topPrograms = $programsCount->take(4)->map(function ($p) {
                // Calculate average units for this program
                $avgUnitsQuery = Student::join('programs', 'students.program_id', '=', 'programs.id')
                    ->where('programs.code', $p->code);

                if (!empty($this->termFilter)) {
                    $avgUnitsQuery->whereHas('enrollees', function ($q) {
                        $q->whereIn('term_id', $this->termFilter);
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
                'averageAge' => $averageAge,
                'averageTerms' => $averageTerms,
                'chart' => [
                    'labels' => $labels,
                    'data' => $data,
                    'colors' => $colors,
                ],
                'topPrograms' => $topPrograms
            ];
        });
    }
}
