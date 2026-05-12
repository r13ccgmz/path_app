<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Models\Semester;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListTermCodes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = AcademicYearResource::class;

    protected static ?string $title = 'Term Codes';

    protected string $view = 'filament.resources.academic-years.pages.list-term-codes';

    public function table(Table $table): Table
    {
        return $table
            ->query(Semester::query()->with('academicYear'))
            ->columns([
                TextColumn::make('term_code')
                    ->label('Term Code')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(query: fn ($query, $direction) =>
                        $query->orderByRaw('CAST(term_code AS UNSIGNED) ' . $direction)
                    )
                    ->copyable(),
                TextColumn::make('semester_period')
                    ->label('Semester')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? $state)
                    ->sortable(),
                TextColumn::make('academicYear.label')
                    ->label('Academic Year')
                    ->sortable(query: fn ($query, $direction) =>
                        $query->join('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
                              ->orderBy('academic_years.year_start', $direction)
                              ->select('semesters.*')
                    ),
                TextColumn::make('start_date')
                    ->label('Start')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('end_date')
                    ->label('End')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_current')
                    ->label('Current')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('semester_period')
                    ->label('Semester Period')
                    ->options(\App\Enums\SemesterPeriod::class),
            ])
            ->defaultSort('term_code', 'desc');
    }
}
