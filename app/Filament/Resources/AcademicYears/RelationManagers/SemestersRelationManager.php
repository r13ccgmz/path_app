<?php

namespace App\Filament\Resources\AcademicYears\RelationManagers;

use App\Enums\SemesterPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;

class SemestersRelationManager extends RelationManager
{
    protected static string $relationship = 'semesters';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->helperText('Only one semester can be current at a time.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('semester_period')
                    ->label('Period')
                    ->formatStateUsing(fn ($state) => $state instanceof SemesterPeriod ? $state->label() : $state),
                TextColumn::make('term_code')
                    ->label('Term Code')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_current')
                    ->label('Current')
                    ->boolean(),
            ])
            ->defaultSort('semester_period')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
