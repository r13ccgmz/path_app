<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Academic Year Details')
                    ->columns(2)
                    ->components([
                        TextInput::make('year_start')
                            ->label('Start Year')
                            ->required()
                            ->numeric()
                            ->minValue(1998)
                            ->maxValue(2100)
                            ->placeholder('e.g., 2024')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('year_end', $state ? (int) $state + 1 : null)
                            ),
                        TextInput::make('year_end')
                            ->label('End Year')
                            ->required()
                            ->numeric()
                            ->minValue(1999)
                            ->maxValue(2101)
                            ->placeholder('e.g., 2025')
                            ->readOnly(),
                        Toggle::make('is_current')
                            ->label('Current Academic Year')
                            ->helperText('Only one academic year can be current at a time.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
