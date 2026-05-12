<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Enrollee;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DashboardStudentOverviewWidget extends Widget
{
    use InteractsWithPageFilters;
    protected string $view = 'filament.widgets.dashboard-student-overview-widget';
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function getTermsProperty(): array
    {
        return \App\Models\Semester::with('academicYear')
            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
            ->toArray();
    }

    public function getOverviewData(): array
    {
        $filterFrom = $this->filters['filterFrom'] ?? null;
        $filterTo = $this->filters['filterTo'] ?? null;
        
        $cacheKey = 'dashboard_overview_data_' . ($filterFrom ?? 'all') . '_' . ($filterTo ?? 'all');
        
        return cache()->remember($cacheKey, 300, function () use ($filterFrom, $filterTo) {
            // Core Metrics from Students Table
            $studentQuery = Student::query();
            
            if (!empty($filterFrom) || !empty($filterTo)) {
                $studentQuery->whereHas('enrollees', function($q) use ($filterFrom, $filterTo) {
                    if (!empty($filterFrom)) $q->where('term_id', '>=', $filterFrom);
                    if (!empty($filterTo)) $q->where('term_id', '<=', $filterTo);
                });
            }

            $totalStudents = (clone $studentQuery)->count();
            $activeStudents = (clone $studentQuery)->where('student_status', 'active')->count();

            // Count graduated students from the graduates table (linked by student_number)
            // This avoids undercounting students who graduated from one program but re-enrolled in another
            // (which resets their student_status back to 'active').
            $graduatedQuery = \App\Models\Graduate::whereNotNull('student_number')
                ->where('student_number', '!=', '');
            if (!empty($filterFrom) || !empty($filterTo)) {
                $graduatedQuery->whereIn('student_number', function($q) use ($filterFrom, $filterTo) {
                    $q->select('student_number')->from('students');
                    if (!empty($filterFrom) || !empty($filterTo)) {
                        $q->whereIn('student_number', function($eq) use ($filterFrom, $filterTo) {
                            $eq->select('student_number')->from('enrollees');
                            if (!empty($filterFrom)) $eq->where('term_id', '>=', $filterFrom);
                            if (!empty($filterTo)) $eq->where('term_id', '<=', $filterTo);
                        });
                    }
                });
            }
            $graduatedStudents = $graduatedQuery->distinct('student_number')->count('student_number');

            $onLeaveStudents = (clone $studentQuery)->whereIn('student_status', ['on-leave', 'on leave'])->count();

            // Demographics from Enrollee Table
            $avgTermsQuery = DB::table('enrollees')
                ->select('student_number', DB::raw('COUNT(DISTINCT term_id) as term_count'));
            
            if (!empty($filterFrom) || !empty($filterTo)) {
                $avgTermsQuery->whereIn('student_number', function($q) use ($filterFrom, $filterTo) {
                    $q->select('student_number')->from('enrollees');
                    if (!empty($filterFrom)) $q->where('term_id', '>=', $filterFrom);
                    if (!empty($filterTo)) $q->where('term_id', '<=', $filterTo);
                });
            }

            $avgTermsRaw = $avgTermsQuery->groupBy('student_number')->get();
            $averageTerms = $avgTermsRaw->count() > 0 ? round($avgTermsRaw->avg('term_count'), 1) : 0;

            // Average Age
            $agesQuery = DB::table('enrollees')
                ->select('student_number', DB::raw('MAX(birthdate) as bdate'))
                ->whereNotNull('birthdate')
                ->where('birthdate', 'not like', '%0000%');

            if (!empty($filterFrom) || !empty($filterTo)) {
                $agesQuery->whereIn('student_number', function($q) use ($filterFrom, $filterTo) {
                    $q->select('student_number')->from('enrollees');
                    if (!empty($filterFrom)) $q->where('term_id', '>=', $filterFrom);
                    if (!empty($filterTo)) $q->where('term_id', '<=', $filterTo);
                });
            }

            $ages = $agesQuery->groupBy('student_number')
                ->get()
                ->map(function($record) {
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
            
            if (!empty($filterFrom) || !empty($filterTo)) {
                $programsQuery->whereHas('enrollees', function($q) use ($filterFrom, $filterTo) {
                    if (!empty($filterFrom)) $q->where('term_id', '>=', $filterFrom);
                    if (!empty($filterTo)) $q->where('term_id', '<=', $filterTo);
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
            $topPrograms = $programsCount->take(4)->map(function ($p) use ($filterFrom, $filterTo) {
                $avgUnitsQuery = Student::join('programs', 'students.program_id', '=', 'programs.id')
                    ->where('programs.code', $p->code);
                
                if (!empty($filterFrom) || !empty($filterTo)) {
                    $avgUnitsQuery->whereHas('enrollees', function($q) use ($filterFrom, $filterTo) {
                        if (!empty($filterFrom)) $q->where('term_id', '>=', $filterFrom);
                        if (!empty($filterTo)) $q->where('term_id', '<=', $filterTo);
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
