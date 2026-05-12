<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\Semester;
use App\Models\Enrollee;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getColumns(): int | array
    {
        return [
            'lg' => 4,
            'xl' => 6,
        ];
    }

    public function getHeading(): string|Htmlable
    {
        return new HtmlString('<h1 class="text-3xl md:text-4xl font-extrabold text-primary-800 dark:text-primary-400 tracking-tight" style="font-family: Avenir, \'Helvetica Neue\', Optima, sans-serif;">Dashboard</h1>');
    }

    public function getSubheading(): string|Htmlable|null
    {
        $name = auth()->user()?->name ?? 'User';
        return "Welcome back, {$name}. Explore student analytics, admissions, and program distributions.";
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\EnrollmentStats::class,
            \App\Filament\Widgets\DashboardStudentOverviewWidget::class,
            \App\Filament\Widgets\DashboardStudentDemographicsChartsWidget::class,
            \App\Filament\Widgets\EnrollmentsPerTermChart::class,
            \App\Filament\Widgets\AdmissionsTrendChart::class,
            \App\Filament\Widgets\AcademicProgressChart::class,
            \App\Filament\Widgets\TermEnrolleeCount::class,
        ];
    }

    public function filtersForm(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        // Get available term codes
        $termCodes = Enrollee::select('term_id')
            ->distinct()
            ->orderBy('term_id')
            ->pluck('term_id')
            ->toArray();

        $semesters = Semester::whereIn('term_code', $termCodes)
            ->with('academicYear')
            ->get()
            ->keyBy('term_code');

        $options = [];
        foreach ($termCodes as $code) {
            $semester = $semesters->get($code);
            $label = $semester ? "[{$code}] {$semester->label}" : "[{$code}]";
            $options[$code] = $label;
        }

        return $form
            ->schema([
                Select::make('filterFrom')
                    ->label('From Term')
                    ->options($options)
                    ->placeholder('All Terms'),
                Select::make('filterTo')
                    ->label('To Term')
                    ->options($options)
                    ->placeholder('All Terms'),
            ])
            ->columns(2);
    }
}
