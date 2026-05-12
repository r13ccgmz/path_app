<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AcademicPerformanceMetrics extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Main';
    protected static ?string $navigationLabel = 'Academic Performance Metrics';
    protected static ?string $title = 'Academic Performance Metrics';
    protected static ?int $navigationSort = 6;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected string $view = 'filament.pages.academic-performance-metrics';
}
