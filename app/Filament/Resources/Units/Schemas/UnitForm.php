<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Unit Details')
                    ->columns(2)
                    ->components([
                        TextInput::make('code')
                            ->label('Unit Code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., IGRD'),
                        TextInput::make('name')
                            ->label('Unit Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Institute for Governance and Rural Development'),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
