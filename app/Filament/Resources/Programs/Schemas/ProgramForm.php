<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Enums\DegreeLevel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Program Details')
                    ->columns(2)
                    ->components([
                        TextInput::make('code')
                            ->label('Program Code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., PhD-DVST'),
                        TextInput::make('name')
                            ->label('Program Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('e.g., Doctor of Philosophy in Development Studies'),
                        Select::make('degree_level')
                            ->label('Degree Level')
                            ->options(DegreeLevel::class)
                            ->required(),
                        TextInput::make('total_units_required')
                            ->label('Total Units Required')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('e.g., 45'),
                        TextInput::make('max_residency_years')
                            ->label('Max Residency (years)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('e.g., 7'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
