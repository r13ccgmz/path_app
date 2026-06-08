<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use App\Enums\SemesterPeriod;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Academic Year Details')
                    ->collapsible()
                    ->columns(6)
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
                            )
                            ->columnSpan(2),
                        TextInput::make('year_end')
                            ->label('End Year')
                            ->required()
                            ->numeric()
                            ->minValue(1999)
                            ->maxValue(2101)
                            ->placeholder('e.g., 2025')
                            ->readOnly()
                            ->columnSpan(2),
                        Toggle::make('is_current')
                            ->label('Current Academic Year')
                            ->helperText('Only one academic year can be current at a time.')
                            ->inline(false)
                            ->columnSpan(2),
                    ]),
                Section::make('Semesters / Term Codes')
                    ->collapsible()
                    ->collapsed()
                    ->components([
                        Repeater::make('semesters')
                            ->relationship('semesters')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('semester_period')
                                    ->label('Semester Period')
                                    ->options(SemesterPeriod::class)
                                    ->required(),
                                TextInput::make('term_code')
                                    ->label('Term Code')
                                    ->required()
                                    ->maxLength(10)
                                    ->unique(ignoreRecord: true)
                                    ->helperText(new \Illuminate\Support\HtmlString(
                                        'Format: <strong>[R][YY][S]</strong><br>' .
                                        '<strong>R</strong> — Rollover number (0 = 1900–1999, 1 = 2000–2099)<br>' .
                                        '<strong>YY</strong> — Academic year start (e.g., 98 for 1998–1999)<br>' .
                                        '<strong>S</strong> — Semester (1 = First, 2 = Second, 3 = Midyear)<br>' .
                                        '<em>Example: AY 1998–1999, 2nd Sem = 0982</em>'
                                    )),
                                DatePicker::make('start_date')
                                    ->label('Start Date'),
                                DatePicker::make('end_date')
                                    ->label('End Date'),
                                Toggle::make('is_current')
                                    ->label('Current Semester')
                                    ->helperText('Only one semester can be current at a time.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
