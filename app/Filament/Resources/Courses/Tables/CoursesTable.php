<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Enums\CourseType;
use App\Models\Program;
use App\Models\Course;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course_code')
                    ->label('Code')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('course_name')
                    ->label('Course Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('program_types')
                    ->label('Course Type')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $types = $record->programCourses()
                            ->whereNotNull('course_type')
                            ->pluck('course_type')
                            ->unique()
                            ->map(fn ($v) => $v instanceof CourseType ? $v->label() : (CourseType::tryFrom($v)?->label() ?? $v))
                            ->values()
                            ->all();
                        return !empty($types) ? $types : ['Unspecified'];
                    })
                    ->color('gray')
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('program_units')
                    ->label('Units')
                    ->getStateUsing(function ($record) {
                        $units = $record->programCourses()
                            ->whereNotNull('units')
                            ->pluck('units')
                            ->unique()
                            ->values()
                            ->all();
                        return !empty($units) ? implode(', ', $units) : '—';
                    })
                    ->alignCenter()
                    ->wrap(),
                TextColumn::make('programs.name')
                    ->label('Programs')
                    ->badge()
                    ->color('success')
                    ->separator(', ')
                    ->limitList(2)
                    ->wrap()
                    ->getStateUsing(fn ($record) => $record->programs->pluck('name')->unique()->values()->all()),
                TextColumn::make('prerequisite_text')
                    ->label('Prerequisite')
                    ->wrap()
                    ->default('—')
                    ->toggleable(),
                TextColumn::make('program_semesters')
                    ->label('Semester Offered')
                    ->getStateUsing(function ($record) {
                        $semesters = $record->programCourses()
                            ->whereNotNull('semester_offered')
                            ->pluck('semester_offered')
                            ->unique()
                            ->values()
                            ->all();
                        return !empty($semesters) ? implode(' · ', $semesters) : '—';
                    })
                    ->wrap()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('program')
                    ->label('Program')
                    ->relationship('programs', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->defaultSort('course_code')
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn (Course $record) => "Edit Course: {$record->course_code}")
                    ->modalWidth('4xl')
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => !auth()->user()->hasRole('viewer')),
            ]);
    }
}
