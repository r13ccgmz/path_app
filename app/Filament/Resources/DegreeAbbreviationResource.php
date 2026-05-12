<?php

namespace App\Filament\Resources;

use App\Models\DegreeAbbreviation;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;

class DegreeAbbreviationResource extends Resource
{
    protected static ?string $model = DegreeAbbreviation::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';


    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 5;


    protected static ?string $navigationLabel = 'Degree Mapping';

    protected static ?string $pluralModelLabel = 'Degree Mapping';

    public static function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('abbreviation')
                    ->label('Abbreviation')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('e.g. MPAf'),
                Forms\Components\TextInput::make('display_name')
                    ->label('Display Name')
                    ->required()
                    ->helperText('Use {major} where the major field should be inserted')
                    ->placeholder('e.g. Master in Public Affairs in {major}'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('abbreviation')
                    ->label('Abbreviation')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Display Name')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
            ])
            ->defaultSort('abbreviation')
            ->recordActions([
                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->fillForm(fn (DegreeAbbreviation $record): array => $record->toArray())
                    ->schema([
                        Forms\Components\TextInput::make('abbreviation')
                            ->label('Abbreviation')
                            ->required(),
                        Forms\Components\TextInput::make('display_name')
                            ->label('Display Name')
                            ->required()
                            ->helperText('Use {major} where the major field should be inserted'),
                    ])
                    ->modalHeading('Edit Degree Mapping')
                    ->modalSubmitActionLabel('Save Changes')
                    ->action(function (DegreeAbbreviation $record, array $data): void {
                        $record->update($data);
                        Notification::make()
                            ->title('Mapping updated')
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (DegreeAbbreviation $record) => $record->delete()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\DegreeAbbreviationResource\Pages\ListDegreeAbbreviations::route('/'),
        ];
    }
}
