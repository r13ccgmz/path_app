<?php

namespace App\Filament\Resources\AcademicYears\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcademicYearsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year_start')
                    ->label('Start')
                    ->sortable(),
                TextColumn::make('year_end')
                    ->label('End')
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Academic Year')
                    ->state(fn ($record) => "AY {$record->year_start}-{$record->year_end}")
                    ->searchable(query: fn ($query, $search) =>
                        $query->where('year_start', 'like', "%{$search}%")
                            ->orWhere('year_end', 'like', "%{$search}%")
                    )
                    ->weight('bold'),
                IconColumn::make('is_current')
                    ->label('Current')
                    ->boolean(),
                TextColumn::make('semesters_count')
                    ->label('Semesters')
                    ->counts('semesters')
                    ->sortable(),
                TextColumn::make('term_codes')
                    ->label('Term Codes')
                    ->state(fn ($record) =>
                        $record->semesters->sortBy(fn ($s) => (int) $s->term_code)->pluck('term_code')->join(' · ')
                    )
                    ->color('gray')
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('semesters', fn ($q) => $q->where('term_code', 'like', "%{$search}%"))
                    )
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('year_start', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
