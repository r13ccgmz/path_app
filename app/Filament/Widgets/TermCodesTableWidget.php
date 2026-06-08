<?php

namespace App\Filament\Widgets;

use App\Models\Semester;
use App\Enums\SemesterPeriod;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Forms;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class TermCodesTableWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Term Codes';
    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(Semester::query()->with('academicYear'))
            ->columns([
                Tables\Columns\TextColumn::make('term_code')
                    ->label('Term Code')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(query: fn ($query, $direction) =>
                        $query->orderByRaw('CAST(term_code AS UNSIGNED) ' . $direction)
                    )
                    ->copyable(),
                Tables\Columns\TextColumn::make('semester_period')
                    ->label('Semester')
                    ->formatStateUsing(fn ($state) => $state instanceof SemesterPeriod ? $state->label() : $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicYear.label')
                    ->label('Academic Year')
                    ->sortable(query: fn ($query, $direction) =>
                        $query->join('academic_years', 'academic_years.id', '=', 'semesters.academic_year_id')
                              ->orderBy('academic_years.year_start', $direction)
                              ->select('semesters.*')
                    ),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_current')
                    ->label('Current')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('semester_period')
                    ->label('Semester Period')
                    ->options(SemesterPeriod::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Term Code')
                    ->model(Semester::class)
                    ->form([
                        Forms\Components\Select::make('academic_year_id')
                            ->label('Academic Year')
                            ->relationship('academicYear', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "AY {$record->year_start}-{$record->year_end}")
                            ->required(),
                        Forms\Components\Select::make('semester_period')
                            ->label('Semester Period')
                            ->options(SemesterPeriod::class)
                            ->required(),
                        Forms\Components\TextInput::make('term_code')
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
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date'),
                        Forms\Components\Toggle::make('is_current')
                            ->label('Current Semester'),
                    ])
                    ->modalWidth('xl')
                    ->after(fn () => $this->dispatch('refreshAcademicYearsPage'))
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        Forms\Components\Select::make('academic_year_id')
                            ->label('Academic Year')
                            ->relationship('academicYear', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "AY {$record->year_start}-{$record->year_end}")
                            ->required(),
                        Forms\Components\Select::make('semester_period')
                            ->label('Semester Period')
                            ->options(SemesterPeriod::class)
                            ->required(),
                        Forms\Components\TextInput::make('term_code')
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
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date'),
                        Forms\Components\Toggle::make('is_current')
                            ->label('Current Semester'),
                    ])
                    ->modalWidth('xl')
                    ->after(fn () => $this->dispatch('refreshAcademicYearsPage'))
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
                DeleteAction::make()
                    ->after(fn () => $this->dispatch('refreshAcademicYearsPage'))
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
            ])
            ->recordAction(fn () => auth()->user()->hasRole('viewer') ? null : 'edit')
            ->defaultSort('term_code', 'desc');
    }
}
