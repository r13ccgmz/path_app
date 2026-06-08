<?php

namespace App\Filament\Resources\Programs\Tables;

use App\Enums\DegreeLevel;
use App\Models\Program;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Program Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('degree_level')
                    ->label('Level')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof DegreeLevel ? $state->label() : $state)
                    ->color(fn ($state) => $state === DegreeLevel::Doctorate ? 'primary' : 'success')
                    ->toggleable(),
                TextColumn::make('total_units_required')
                    ->label('Units')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('specialization_names')
                    ->label('Specializations')
                    ->badge()
                    ->color('info')
                    ->separator(', ')
                    ->limitList(2)
                    ->wrap()
                    ->getStateUsing(fn ($record) => $record->majors->pluck('name')->unique()->values()->all())
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('degree_level')
                    ->options(DegreeLevel::class),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn (Program $record) => "Edit Program: {$record->name}")
                    ->modalWidth('4xl')
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
                DeleteAction::make()
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => !auth()->user()->hasRole('viewer')),
            ]);
    }
}
