<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\AcademicOutput;

class AcademicOutputStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Academic Outputs', AcademicOutput::count())
                ->description('All recorded outputs')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('primary'),
            Stat::make('Approved Outputs', AcademicOutput::where('status', 'approved')->count())
                ->description('Finalized & approved')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Ongoing Research', AcademicOutput::whereIn('status', [
                    'topic-approved', 'proposal-writing', 'proposal-defended', 'data-collection', 'writing', 'revising'
                ])->count())
                ->description('Currently in progress')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),
        ];
    }
}
