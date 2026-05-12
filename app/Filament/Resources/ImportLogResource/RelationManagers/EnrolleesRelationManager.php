<?php

namespace App\Filament\Resources\ImportLogResource\RelationManagers;

use App\Models\Enrollee;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrolleesRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollees';

    protected static ?string $title = 'Affected Rows';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-table-cells';

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
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Student Number')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => Enrollee::formatStudentNumber($state)),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->label('Middle Name')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('term_id')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('degree_program')
                    ->label('Degree/Program')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_units')
                    ->label('Units')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('student_number')
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'imported' => 'Imported (New)',
                        'updated' => 'Updated',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, string $value): Builder => $query->wherePivot('action', $value)
                        );
                    }),
            ])
            ->paginated([10, 25, 50, 100])
            ->heading('Affected Enrollee Records')
            ->description('Enrollees created or updated by this import. Green = newly imported, Blue = updated existing.')
            ->emptyStateHeading('No tracked rows')
            ->emptyStateDescription('This import was performed before row tracking was enabled.')
            ->emptyStateIcon('heroicon-o-table-cells');
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->import_type === 'enrollee';
    }
}
