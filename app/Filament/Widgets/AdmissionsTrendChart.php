<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasTermRangeFilter;
use App\Models\Semester;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdmissionsTrendChart extends ChartWidget
{
    use HasTermRangeFilter;

    protected ?string $heading = 'Admissions Trend';
    
    public function getDescription(): ?string
    {
        return 'Data is sourced from manual admission records. If the chart is empty, no admission dates have been recorded yet.';
    }
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = ['lg' => 2, 'xl' => 3];

    protected string $view = 'filament.widgets.term-range-chart';

    protected function getData(): array
    {
        $admissions = DB::table('students')
            ->join('semesters', 'students.admission_semester_id', '=', 'semesters.id')
            ->selectRaw('semesters.term_code as first_term, students.id as student_id')
            ->whereNotNull('students.admission_semester_id')
            ->whereNull('students.deleted_at')
            ->get();

        $termCounts = [];
        foreach ($admissions as $admin) {
            $termCounts[$admin->first_term] = ($termCounts[$admin->first_term] ?? 0) + 1;
        }

        ksort($termCounts);

        // Apply From/To filter
        $allTermCodes = array_keys($termCounts);
        $filteredCodes = $this->filterTermRange($allTermCodes);
        $this->buildTermDescriptions($filteredCodes);

        $filteredCounts = [];
        foreach ($filteredCodes as $code) {
            $filteredCounts[$code] = $termCounts[$code] ?? 0;
        }

        $labels = array_map('strval', array_keys($filteredCounts));
        $data = array_values($filteredCounts);

        return [
            'datasets' => [
                [
                    'label' => 'New Admits',
                    'data' => $data,
                    'borderColor' => '#1A5C38',
                    'backgroundColor' => 'rgba(26, 92, 56, 0.15)',
                    'fill' => 'start',
                    'tension' => 0.4,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#1A5C38',
                    'borderWidth' => 2,
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
        return 'line';
    }
}
