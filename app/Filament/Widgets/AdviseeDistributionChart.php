<?php

namespace App\Filament\Widgets;

use App\Models\Faculty;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdviseeDistributionChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Advisee Distribution (Top 15 Faculty)';
    protected int | string | array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return "Bar chart of active faculty with the most advisees, split by Master's vs PhD.";
    }

    protected function getData(): array
    {
        $faculty = Faculty::where('is_external', false)
            ->where('faculty_status', 'active')
            ->withCount([
                'advisees as masters_count' => function ($q) {
                    $q->whereHas('program', fn ($p) => $p->whereIn('degree_level', ['master', 'master_of_science']));
                },
                'advisees as phd_count' => function ($q) {
                    $q->whereHas('program', fn ($p) => $p->where('degree_level', 'doctorate'));
                },
            ])
            ->get()
            ->filter(fn ($f) => ($f->masters_count + $f->phd_count) > 0)
            ->sortByDesc(fn ($f) => $f->masters_count + $f->phd_count)
            ->take(15);

        $labels = $faculty->map(fn ($f) => $f->last_name . ', ' . substr($f->first_name, 0, 1) . '.')->values()->toArray();
        $mastersData = $faculty->pluck('masters_count')->values()->toArray();
        $phdData = $faculty->pluck('phd_count')->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => "Master's",
                    'data' => $mastersData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'PhD',
                    'data' => $phdData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => ['display' => false],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
            'plugins' => [
                'legend' => ['position' => 'top'],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
