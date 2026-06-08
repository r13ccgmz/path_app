<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportLogResource\Pages;
use App\Models\ImportLog;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ImportLogResource extends Resource
{
    protected static ?string $model = ImportLog::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';


    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 8;


    protected static ?string $navigationLabel = 'Import Logs';

    protected static ?string $modelLabel = 'Import Log';


    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Import Summary')
                    ->schema([
                        TextEntry::make('filename')->label('File'),
                        TextEntry::make('created_at')->label('Uploaded At')->dateTime(),
                        TextEntry::make('user.name')->label('Uploaded By'),
                        TextEntry::make('total_rows')
                            ->label('Total Rows Processed')
                            ->badge()
                            ->color('primary')
                            ->size(\Filament\Support\Enums\TextSize::Large),
                        TextEntry::make('rows_imported')
                            ->label('Imported')
                            ->badge()
                            ->color('success'),
                        TextEntry::make('rows_updated')
                            ->label('Updated')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('rows_unchanged')
                            ->label('Unchanged')
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('rows_rejected')
                            ->label('Rejected')
                            ->badge()
                            ->color('danger'),
                    ])->columns(3),
                Section::make('Affected Rows with Errors')
                    ->schema([
                        RepeatableEntry::make('errors')
                            ->label('')
                            ->schema([
                                TextEntry::make('row')->label('Row #'),
                                TextEntry::make('attribute')->label('Field'),
                                TextEntry::make('errors')
                                    ->label('Error')
                                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),
                                TextEntry::make('values')
                                    ->label('Row Data')
                                    ->formatStateUsing(fn ($state) => is_array($state)
                                        ? collect($state)->map(fn ($v, $k) => "$k: $v")->implode(' | ')
                                        : ($state ?? '—')),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible()
                    ->visible(fn (ImportLog $record): bool => !empty($record->errors)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->label('File')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('import_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'graduate' => 'info',
                        default => 'success',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_rows')
                    ->label('Total')
                    ->badge()
                    ->color('primary')
                    ->size(\Filament\Support\Enums\TextSize::Large)
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw('(rows_imported + rows_updated + rows_unchanged + rows_rejected) ' . $direction);
                    }),
                Tables\Columns\TextColumn::make('rows_imported')
                    ->label('Imported')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rows_updated')
                    ->label('Updated')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rows_unchanged')
                    ->label('Unchanged')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rows_rejected')
                    ->label('Rejected')
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Uploaded By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ImportLogResource\RelationManagers\EnrolleesRelationManager::class,
            \App\Filament\Resources\ImportLogResource\RelationManagers\GraduatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportLogs::route('/'),
            'view' => Pages\ViewImportLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
