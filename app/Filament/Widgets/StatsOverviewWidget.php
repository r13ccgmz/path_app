<?php

namespace App\Filament\Widgets;

use App\Models\Enrollee;
use App\Models\Course;
use App\Models\Program;
use App\Models\ImportLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Unique Students', \App\Models\Student::count())
                ->description('All-time recorded')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Total Programs', Program::count())
                ->description('Active degree programs')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
            Stat::make('Total Courses', Course::count())
                ->description('In course catalog')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info'),
            Stat::make('Processed Imports', ImportLog::count())
                ->description('Data batches')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('warning'),
        ];
    }
}
