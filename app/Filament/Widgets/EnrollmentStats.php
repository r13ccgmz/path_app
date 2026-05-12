<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Enrollee;
use App\Models\Student;
use App\Models\Graduate;
use App\Models\Faculty;
use App\Models\AcademicOutput;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class EnrollmentStats extends BaseWidget
{
    use InteractsWithPageFilters;
    protected static ?int $sort = 1;
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $filterFrom = $this->filters['filterFrom'] ?? null;
        $filterTo = $this->filters['filterTo'] ?? null;

        $enrolleeQuery = Enrollee::query();
        if (!empty($filterFrom)) {
            $enrolleeQuery->where('term_id', '>=', $filterFrom);
        }
        if (!empty($filterTo)) {
            $enrolleeQuery->where('term_id', '<=', $filterTo);
        }

        // Apply filters to students if terms are filtered
        $studentQuery = Student::query();
        if (!empty($filterFrom) || !empty($filterTo)) {
            $studentQuery->whereHas('enrollees', function($q) use ($filterFrom, $filterTo) {
                if (!empty($filterFrom)) $q->where('term_id', '>=', $filterFrom);
                if (!empty($filterTo)) $q->where('term_id', '<=', $filterTo);
            });
        }

        // Apply filters to graduates by semester_graduated (term_code)
        $graduateQuery = Graduate::query();
        if (!empty($filterFrom)) {
            $graduateQuery->where('semester_graduated', '>=', $filterFrom);
        }
        if (!empty($filterTo)) {
            $graduateQuery->where('semester_graduated', '<=', $filterTo);
        }

        // Apply filters to academic outputs by term_code
        $aoQuery = AcademicOutput::query();
        if (!empty($filterFrom)) {
            $aoQuery->where('term_code', '>=', $filterFrom);
        }
        if (!empty($filterTo)) {
            $aoQuery->where('term_code', '<=', $filterTo);
        }

        return [
            Stat::make('Total Enrollments', number_format($enrolleeQuery->count()))
                ->description(empty($filterFrom) && empty($filterTo) ? 'All terms combined' : 'For selected terms')
                ->icon('heroicon-o-academic-cap')
                ->color('success')
                ->extraAttributes(['class' => 'bg-white dark:bg-gray-900 border-none shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl']),
            Stat::make('Unique Students', number_format($studentQuery->count()))
                ->description(empty($filterFrom) && empty($filterTo) ? 'All-time recorded' : 'In selected terms')
                ->icon('heroicon-o-users')
                ->color('primary')
                ->extraAttributes(['class' => 'bg-white dark:bg-gray-900 border-none shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl']),
            Stat::make('Graduate Records', number_format($graduateQuery->count()))
                ->description(
                    empty($filterFrom) && empty($filterTo)
                        ? Graduate::whereNotNull('student_number')->where('student_number', '!=', '')->distinct('student_number')->count('student_number') . ' unique students linked'
                        : 'In selected terms'
                )
                ->icon('heroicon-o-check-badge')
                ->color('info')
                ->extraAttributes(['class' => 'bg-white dark:bg-gray-900 border-none shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl']),
            Stat::make('Faculty', number_format(Faculty::where('faculty_status', 'active')->where('is_external', false)->count()))
                ->description('Active internal faculty')
                ->icon('heroicon-o-user-group')
                ->color('warning')
                ->extraAttributes(['class' => 'bg-white dark:bg-gray-900 border-none shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl']),
            Stat::make('Academic Outputs', number_format($aoQuery->count()))
                ->description(empty($filterFrom) && empty($filterTo) ? 'Theses, dissertations, etc.' : 'In selected terms')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->extraAttributes(['class' => 'bg-white dark:bg-gray-900 border-none shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl']),
        ];
    }
}
