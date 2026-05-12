<?php

namespace App\Filament\Widgets;

use App\Models\EnrollmentCourse;
use App\Models\StudentEnrollment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class RawCoursesWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Distinct Raw Courses';
    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        // Get courses and their enrollment count
        return $table
            ->query(
                EnrollmentCourse::query()
                    ->withCount('enrollees')
            )
            ->defaultSort('enrollees_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('course_code')
                    ->label('Course Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course_name')
                    ->label('Course Name')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),
                Tables\Columns\TextColumn::make('enrollees_count')
                    ->label('Enrollees')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->alignEnd(),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
