<?php

namespace App\Filament\Resources\CognateFields\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CognateFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cognate Field Details')
                    ->components([
                        TextInput::make('name')
                            ->label('Field Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., Strategic Planning and Policy Studies'),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                    ]),
            ]);
    }
}
