<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Enrollee;
use Illuminate\Support\Facades\DB;

class TermsEnrolleeChart extends ChartWidget
{
    protected ?string $heading = 'Enrollee Count per Term';
    protected ?string $maxHeight = '350px';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $raw = Enrollee::query()
            ->select('term_id', DB::raw('COUNT(DISTINCT student_number) AS enrollee_cnt'))
            ->groupBy('term_id')
            ->orderBy('term_id')
            ->get()
            ->pluck('enrollee_cnt', 'term_id')
            ->toArray();

        return [
            'labels' => array_keys($raw),

            'datasets' => [
                [
                    'label' => 'Students',
                    'data' => array_values($raw),

                    'backgroundColor' => 'rgba(26, 92, 56, 0.15)', // CPAf Forest Green
                    'borderColor' => '#1A5C38',
                    'borderWidth' => 2,

                    'tension' => 0.4,
                    'fill' => 'start',

                    'pointRadius' => 0,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#1A5C38',
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,

            'animation' => [
                'duration' => 800,
                'easing' => 'easeOutQuart',
            ],

            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'border' => [
                        'display' => false,
                        'dash' => [5, 5],
                    ],
                ],
            ],

            'elements' => [
                'line' => [
                    'tension' => 0.4,
                ],
            ],

            'plugins' => [
                'legend' => [
                    'labels' => [
                        'font' => [
                            'family' => 'Avenir, Helvetica Neue, Optima, sans-serif',
                            'size' => 14,
                        ],
                    ],
                ],
            ],
        ];
    }
}
