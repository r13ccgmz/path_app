<?php

namespace App\Filament\Widgets;

use App\Models\ImportLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestImportsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Recent Data Imports';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ImportLog::query()->latest()
            )
            ->columns([
                TextColumn::make('filename')
                    ->label('File Name')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    }),
                TextColumn::make('import_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'grades' => 'info',
                        'graduates' => 'success',
                        'admissions' => 'warning',
                        default => 'primary',
                    }),
                TextColumn::make('rows_imported')
                    ->label('Imported')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('rows_rejected')
                    ->label('Rejected')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray')
                    ->alignEnd(),
                TextColumn::make('created_at')
                    ->label('Processed On')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->alignEnd(),
            ])
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }
}
