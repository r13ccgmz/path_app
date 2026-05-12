<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Enrollee;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DashboardStudentDemographicsChartsWidget extends Widget
{
    use InteractsWithPageFilters;
    protected string $view = 'filament.widgets.dashboard-student-demographics-charts-widget';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function getTermsProperty(): array
    {
        return \App\Models\Semester::with('academicYear')
            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
            ->toArray();
    }

    public function getDemographicsData(): array
    {
        $filterFrom = $this->filters['filterFrom'] ?? null;
        $filterTo = $this->filters['filterTo'] ?? null;

        $cacheKey = 'dashboard_demographics_data_' . ($filterFrom ?? 'all') . '_' . ($filterTo ?? 'all');
        
        return cache()->remember($cacheKey, 300, function () use ($filterFrom, $filterTo) {
            // 2. Gender Distribution (from Enrollee)
            $genderQuery = DB::table('enrollees')
                ->select('sex', DB::raw('COUNT(DISTINCT student_number) as count'))
                ->whereNotNull('sex')
                ->where('sex', '!=', '');
                
            if (!empty($filterFrom)) {
                $genderQuery->where('term_id', '>=', $filterFrom);
            }
            if (!empty($filterTo)) {
                $genderQuery->where('term_id', '<=', $filterTo);
            }
                
            $genderCounts = $genderQuery->groupBy('sex')->get();

            $genderMap = [
                'F' => 'Female',
                'M' => 'Male',
                'female' => 'Female',
                'male' => 'Male',
            ];

            $genders = ['Female' => 0, 'Male' => 0, 'Other' => 0];
            foreach ($genderCounts as $g) {
                $label = $genderMap[$g->sex] ?? 'Other';
                $genders[$label] += $g->count;
            }

            // Clean up zeros
            $genders = array_filter($genders, fn($val) => $val > 0);

            // 3. Full-time vs Part-time
            $threshold = (int) \App\Models\SystemSetting::get('full_time_units_threshold', 9);
            $ftPtQuery = DB::table('enrollees')
                ->select(DB::raw('CASE WHEN total_units >= ' . $threshold . ' THEN "Full-Time" ELSE "Part-Time" END as status'), DB::raw('COUNT(DISTINCT student_number) as count'));
                
            if (!empty($filterFrom)) {
                $ftPtQuery->where('term_id', '>=', $filterFrom);
            }
            if (!empty($filterTo)) {
                $ftPtQuery->where('term_id', '<=', $filterTo);
            }
                
            $ftPtCounts = $ftPtQuery->groupBy('status')->get();

            $ftPtLabels = [];
            $ftPtData = [];
            foreach ($ftPtCounts as $ftpt) {
                $ftPtLabels[] = $ftpt->status;
                $ftPtData[] = $ftpt->count;
            }

            // 4. Nationality Profile (from Enrollee)
            $natQuery = DB::table('enrollees')
                ->select('nationality', DB::raw('COUNT(DISTINCT student_number) as count'))
                ->whereNotNull('nationality')
                ->where('nationality', '!=', '');
                
            if (!empty($filterFrom)) {
                $natQuery->where('term_id', '>=', $filterFrom);
            }
            if (!empty($filterTo)) {
                $natQuery->where('term_id', '<=', $filterTo);
            }
                
            $natCounts = $natQuery->groupBy('nationality')->get();

            $nationalities = ['Filipino' => 0, 'International' => 0];
            $internationalBreakdown = [];

            foreach ($natCounts as $n) {
                $nats = json_decode($n->nationality, true);
                if (!$nats || !is_array($nats)) {
                    $nats = [$n->nationality];
                }

                foreach ($nats as $nat) {
                    if (strtolower($nat) === 'filipino') {
                        $nationalities['Filipino'] += $n->count;
                    } else {
                        $nationalities['International'] += $n->count;
                        $internationalBreakdown[$nat] = ($internationalBreakdown[$nat] ?? 0) + $n->count;
                    }
                }
            }

            return [
                'gender' => [
                    'labels' => array_keys($genders),
                    'data' => array_values($genders),
                    'colors' => ['#ec4899', '#1A2B6B', '#9ca3af'] // Pink, CPAf Navy, Gray
                ],
                'enrollment' => [
                    'threshold' => $threshold,
                    'labels' => $ftPtLabels,
                    'data' => $ftPtData,
                    'colors' => ['#1A5C38', '#f59e0b'] // CPAf Green, Amber
                ],
                'nationality' => [
                    'summary' => [
                        'labels' => array_keys($nationalities),
                        'data' => array_values($nationalities),
                        'colors' => ['#1A2B6B', '#ef4444'] // CPAf Navy, Red
                    ],
                    'breakdown' => $internationalBreakdown
                ]
            ];
        });
    }
}
