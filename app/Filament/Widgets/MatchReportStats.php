<?php

namespace App\Filament\Widgets;

use App\Models\Graduate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MatchReportStats extends BaseWidget
{
    // Only shown on the MatchReport page (registered via getHeaderWidgets)
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int|array|null
    {
        return 4;
    }

    protected function getStats(): array
    {
        $total = Graduate::count();
        $auto = Graduate::where('match_type', 'auto')->count();
        $manual = Graduate::where('match_type', 'manual')->count();
        $unmatched = Graduate::where(fn ($q) => $q->whereNull('student_number')->orWhere('student_number', ''))->count();

        return [
            Stat::make('Total Graduates', $total)
                ->description('All imported graduates')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('gray'),
            Stat::make('Auto-matched', $auto)
                ->description('Matched automatically')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('info'),
            Stat::make('Manual', $manual)
                ->description('Matched manually')
                ->descriptionIcon('heroicon-m-hand-raised')
                ->color('success'),
            Stat::make('Needs Manual', $unmatched)
                ->description('Awaiting match')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
