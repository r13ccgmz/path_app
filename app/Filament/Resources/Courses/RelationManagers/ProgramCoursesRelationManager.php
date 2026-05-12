<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Enums\CourseType;
use App\Models\Program;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;

class ProgramCoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'programCourses';

    protected static ?string $title = 'Program Links';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('program_id')
                    ->label('Program')
                    ->relationship('program', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Program $record) => "{$record->name} ({$record->code})")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit')
                    ->columnSpanFull(),
                Select::make('course_type')
                    ->label('Course Type in Program')
                    ->options(CourseType::class)
                    ->required()
                    ->live(),
                Select::make('semester_offered')
                    ->label('Semester Offered')
                    ->options([
                        'First Semester' => 'First Semester',
                        'Second Semester' => 'Second Semester',
                        'First and Second Semester' => 'First and Second Semester',
                        'Midyear' => 'Midyear',
                        'First Semester, Second Semester, and Midyear' => 'First Semester, Second Semester, and Midyear',
                    ])
                    ->searchable()
                    ->placeholder('Select semester...'),
                Select::make('cognate_field_id')
                    ->label('Cognate Field')
                    ->options(\App\Models\CognateField::pluck('name', 'id'))
                    ->searchable()
                    ->hidden(function (Get $get) {
                        $type = $get('course_type');
                        return ($type instanceof \App\Enums\CourseType ? $type->value : $type) !== 'cognate';
                    })
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                    ])
                    ->createOptionUsing(function (array $data) {
                        return \App\Models\CognateField::create($data)->id;
                    })
                    ->preload(),
                TextInput::make('units')
                    ->label('Units')
                    ->numeric()
                    ->minValue(0)
                    ->placeholder('e.g., 3'),
                TextInput::make('prerequisite_text')
                    ->label('Prerequisites')
                    ->maxLength(500),
                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2),
                Toggle::make('is_required')
                    ->label('Required')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('program.code')
                    ->label('Code')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('course_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof CourseType ? $state->label() : (CourseType::tryFrom($state)?->label() ?? $state)),
                TextColumn::make('semester_offered')
                    ->label('Semester')
                    ->default('—'),
                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
                TextColumn::make('units')
                    ->label('Units')
                    ->default('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Link to Program'),
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
