<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasTermRangeFilter;
use App\Models\Enrollee;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EnrollmentsPerTermChart extends ChartWidget
{
    use HasTermRangeFilter;

    protected ?string $heading = 'Enrollments per Term';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '320px';
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.term-range-chart';

    protected function getData(): array
    {
        $termData = Enrollee::query()
            ->select('term_id', DB::raw('COUNT(*) as count'))
            ->groupBy('term_id')
            ->orderBy('term_id')
            ->get();

        $allTermCodes = $termData->pluck('term_id')->toArray();
        $filteredCodes = $this->filterTermRange($allTermCodes);
        $this->buildTermDescriptions($filteredCodes);

        $filtered = $termData->whereIn('term_id', $filteredCodes);
        $termCodes = $filtered->pluck('term_id')->toArray();
        $counts = $filtered->pluck('count')->toArray();

        $backgroundColors = [];
        $borderColors = [];
        foreach ($termCodes as $code) {
            $semType = substr((string) $code, -1);
            switch ($semType) {
                case '1':
                    $backgroundColors[] = 'rgba(22, 163, 74, 0.7)';
                    $borderColors[] = '#16a34a';
                    break;
                case '2':
                    $backgroundColors[] = 'rgba(26, 43, 107, 0.7)';
                    $borderColors[] = '#1A2B6B';
                    break;
                case '3':
                    $backgroundColors[] = 'rgba(234, 179, 8, 0.7)';
                    $borderColors[] = '#eab308';
                    break;
                default:
                    $backgroundColors[] = 'rgba(156, 163, 175, 0.7)';
                    $borderColors[] = '#9ca3af';
            }
        }

        return [
            'datasets' => [[
                'label' => 'Enrollees',
                'data' => $counts,
                'backgroundColor' => $backgroundColors,
                'borderColor' => $borderColors,
                'borderWidth' => 1,
                'borderRadius' => 4,
            ]],
            'labels' => array_map('strval', $termCodes),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false]
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'border' => ['display' => false],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'border' => ['display' => false],
                    'grid' => ['color' => 'rgba(156, 163, 175, 0.1)'],
                ],
            ],
            'layout' => [
                'padding' => 20
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
