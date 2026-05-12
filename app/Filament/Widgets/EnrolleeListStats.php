<?php

namespace App\Filament\Widgets;

use App\Models\Enrollee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EnrolleeListStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalEnrollees = Enrollee::count();
        $uniqueStudents = Enrollee::distinct('student_number')->count('student_number');
        $latestTerm = Enrollee::max('term_id');
        $latestTermCount = $latestTerm ? Enrollee::where('term_id', $latestTerm)->count() : 0;
        $programCount = Enrollee::distinct('degree_program')->count('degree_program');

        return [
            Stat::make('Total Enrollment Records', number_format($totalEnrollees))
                ->description('All terms combined')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),
            Stat::make('Unique Students', number_format($uniqueStudents))
                ->description('Distinct student numbers')
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make("Latest Term ({$latestTerm})", number_format($latestTermCount))
                ->description('Records in most recent term')
                ->icon('heroicon-o-calendar')
                ->color('info'),
            Stat::make('Programs', number_format($programCount))
                ->description('Distinct degree programs')
                ->icon('heroicon-o-academic-cap')
                ->color('warning'),
        ];
    }
}
