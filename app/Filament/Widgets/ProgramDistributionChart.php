<?php

namespace App\Filament\Widgets;

use App\Models\Enrollee;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class ProgramDistributionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Top Enrollment Programs';
    protected int | string | array $columnSpan = ['lg' => 2, 'xl' => 3];
    protected ?string $maxHeight = '320px';
    protected string $view = 'filament.widgets.term-range-chart';
    protected static bool $isDiscovered = false;

    public ?string $filter = 'top5';

    protected function getFilters(): ?array
    {
        return [
            'top5' => 'Top 5 Programs',
            'top10' => 'Top 10 Programs',
            'all' => 'All Programs',
        ];
    }

    protected function getData(): array
    {
        $filterFrom = $this->filters['filterFrom'] ?? null;
        $filterTo = $this->filters['filterTo'] ?? null;

        $query = Enrollee::query()
            ->select('degree_program', DB::raw('COUNT(DISTINCT student_number) as student_count'))
            ->whereNotNull('degree_program')
            ->where('degree_program', '!=', '');

        if (!empty($filterFrom)) {
            $query->where('term_id', '>=', $filterFrom);
        }
        if (!empty($filterTo)) {
            $query->where('term_id', '<=', $filterTo);
        }

        $allData = $query->groupBy('degree_program')
            ->orderByDesc('student_count')
            ->get();

        $topN = match ($this->filter) {
            'top10' => 10,
            'all' => $allData->count(),
            default => 5,
        };

        $top = $allData->take($topN);
        $othersCount = $allData->skip($topN)->sum('student_count');

        $totalSum = $allData->sum('student_count');

        $labels = [];
        $dataValues = [];
        
        $backgroundColor = [
            '#0284c7', '#16a34a', '#ca8a04', '#dc2626', '#7c3aed',
            '#0891b2', '#d97706', '#be123c', '#4f46e5', '#059669',
        ];

        foreach ($top as $i => $row) {
            $labels[] = $row->degree_program;
            $dataValues[] = $row->student_count;
        }

        if ($othersCount > 0) {
            $labels[] = 'Other Programs';
            $dataValues[] = $othersCount;
            $backgroundColor[] = '#9ca3af'; // Gray 400
        }

        $formattedLabels = array_map(function ($label, $value) use ($totalSum) {
            $percent = $totalSum > 0 ? round(($value / $totalSum) * 100, 1) : 0;
            return "{$label} ({$percent}%)";
        }, $labels, $dataValues);

        return [
            'datasets' => [
                [
                    'label' => 'Students',
                    'data' => $dataValues,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
            'labels' => $formattedLabels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
            ],
            'cutout' => '75%',
            'borderWidth' => 0,
            'layout' => [
                'padding' => 20
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
