<?php

namespace App\Filament\Resources\ImportLogResource\RelationManagers;

use App\Models\Enrollee;
use App\Models\Graduate;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GraduatesRelationManager extends RelationManager
{
    protected static string $relationship = 'graduates';

    protected static ?string $title = 'Affected Graduate Records';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-academic-cap';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pivot.action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'imported' => 'success',
                        'updated' => 'info',
                        'skipped' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('degree')
                    ->label('Degree')
                    ->sortable(),
                Tables\Columns\TextColumn::make('program_name')
                    ->label('Program')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('semester_graduated')
                    ->label('Semester')
                    ->sortable()
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '—';
                        if (is_numeric($state)) {
                            $sem = \App\Models\Semester::where('term_code', $state)->first();
                            return $sem ? $sem->short_label : $state;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Student #')
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => $state ? Enrollee::formatStudentNumber($state) : '—'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'imported' => 'Imported (New)',
                        'updated' => 'Updated',
                        'skipped' => 'Skipped',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, string $value): Builder => $query->wherePivot('action', $value)
                        );
                    }),
            ])
            ->paginated([10, 25, 50, 100])
            ->heading('Affected Graduate Records')
            ->description('Graduates created or updated by this import. Green = newly imported, Blue = updated existing.')
            ->emptyStateHeading('No tracked rows')
            ->emptyStateDescription('This import was performed before row tracking was enabled.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->import_type === 'graduate';
    }
}
