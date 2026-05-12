<?php

namespace App\Filament\Widgets;

use App\Models\Enrollee;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class RawProgramsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Distinct Raw Programs';
    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Enrollee::query()
                    ->select('degree_program', DB::raw('COUNT(DISTINCT student_number) as student_count'))
                    ->whereNotNull('degree_program')
                    ->where('degree_program', '!=', '')
                    ->groupBy('degree_program')
            )
            ->defaultSort('student_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('degree_program')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student_count')
                    ->label('Unique Students')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->alignEnd(),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model | array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['degree_program'] ?? '');
        }
        return (string) $record->degree_program;
    }
}
