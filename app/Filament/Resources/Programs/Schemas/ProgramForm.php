<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Enums\DegreeLevel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Program Details')
                    ->collapsible()
                    ->columns(6)
                    ->components([
                        TextInput::make('code')
                            ->label('Program Code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., PhD-DVST')
                            ->columnSpan(2),
                        Select::make('degree_level')
                            ->label('Degree Level')
                            ->options(DegreeLevel::class)
                            ->required()
                            ->columnSpan(2),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                        TextInput::make('name')
                            ->label('Program Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6)
                            ->placeholder('e.g., Doctor of Philosophy in Development Studies'),
                        TextInput::make('total_units_required')
                            ->label('Total Units Required')
                            ->numeric()
                            ->disabled()
                            ->helperText('Managed from the Curriculum Map page.')
                            ->placeholder('Set via Curriculum Map')
                            ->columnSpan(3),
                        TextInput::make('max_residency_years')
                            ->label('Max Residency (years)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('e.g., 7')
                            ->columnSpan(3),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpan(6),
                    ]),
                Section::make('Specializations / Majors')
                    ->collapsible()
                    ->collapsed()
                    ->components([
                        Repeater::make('majors')
                            ->relationship('majors')
                            ->hiddenLabel()
                            ->grid(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Specialization Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Local Governance and Development'),
                                TextInput::make('description')
                                    ->label('Description')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Optional description'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Requirements')
                    ->collapsible()
                    ->collapsed()
                    ->components([
                        Repeater::make('requirements')
                            ->relationship('requirements')
                            ->hiddenLabel()
                            ->simple(
                                TextInput::make('requirement_text')
                                    ->placeholder('e.g., Minimum of 14 units of Core Courses')
                                    ->required()
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
