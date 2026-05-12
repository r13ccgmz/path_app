<?php

namespace App\Filament\Widgets;

use App\Models\StudentProgram;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AcademicProgressChart extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = ['lg' => 2, 'xl' => 3];
    protected string $view = 'filament.widgets.dashboard-academic-progress-widget';
    protected static bool $isDiscovered = false;

    public function getProgressData(): array
    {
        $filterFrom = $this->filters['filterFrom'] ?? null;
        $filterTo = $this->filters['filterTo'] ?? null;

        $cacheKey = 'dashboard_academic_progress_' . ($filterFrom ?? 'all') . '_' . ($filterTo ?? 'all');

        return cache()->remember($cacheKey, 300, function () use ($filterFrom, $filterTo) {
            $studentsQuery = StudentProgram::with('program')
                ->selectRaw('student_programs.*, (
                    SELECT COALESCE(SUM(se.units_earned), 0)
                    FROM student_enrollments se
                    WHERE se.student_program_id = student_programs.id
                    AND se.status = ?
                ) AS live_units_earned', ['completed']);

            // Simple proxy for term filter on the student
            if (!empty($filterFrom) || !empty($filterTo)) {
                $studentsQuery->whereHas('student.enrollees', function($q) use ($filterFrom, $filterTo) {
                    if (!empty($filterFrom)) $q->where('term_id', '>=', $filterFrom);
                    if (!empty($filterTo)) $q->where('term_id', '<=', $filterTo);
                });
            }

            $students = $studentsQuery->get();

            $graduated = 0;
            $active0to25 = 0;
            $active26to50 = 0;
            $active51to75 = 0;
            $active76to99 = 0;
            $activeCompleted = 0;

            foreach ($students as $sp) {
                if ($sp->status === 'graduated') {
                    $graduated++;
                    continue;
                }

                $required = $sp->program?->total_units_required ?? 0;
                if ($required <= 0) {
                    $active0to25++;
                    continue;
                }

                $earned = (int) ($sp->live_units_earned ?? $sp->total_units_earned ?? 0);
                $pct = ($earned / $required) * 100;

                if ($pct >= 100) {
                    $activeCompleted++;
                } elseif ($pct >= 76) {
                    $active76to99++;
                } elseif ($pct >= 51) {
                    $active51to75++;
                } elseif ($pct >= 26) {
                    $active26to50++;
                } else {
                    $active0to25++;
                }
            }

            $total = $students->count();

            $data = [$active0to25, $active26to50, $active51to75, $active76to99, $activeCompleted, $graduated];
            $colors = ['#FCA5A5', '#FCD34D', '#86EFAC', '#34D399', '#1A5C38', '#1A2B6B'];
            $labels = ['0-25% (Early)', '26-50% (Mid)', '51-75% (Advanced)', '76-99% (Near Done)', '100% (Candidates)', 'Graduated'];

            return [
                'total' => $total,
                'labels' => $labels,
                'data' => $data,
                'colors' => $colors,
            ];
        });
    }
}
