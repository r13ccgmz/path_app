<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Enums\CourseType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\CognateField;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Course Information')
                    ->columns(2)
                    ->components([
                        TextInput::make('course_code')
                            ->label('Course Code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., AERS 282'),
                        TextInput::make('course_name')
                            ->label('Course Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Social Research Design'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                Section::make('Initial Program Links')
                    ->description('Optionally link this course to programs right now. You can also manage links later using the Curriculum Map or the Course Edit page.')
                    ->hiddenOn('edit')
                    ->components([
                        \Filament\Forms\Components\Repeater::make('programCourses')
                            ->relationship()
                            ->hiddenLabel()
                            ->addActionLabel('Add Program Link')
                            ->columns(2)
                            ->schema([
                                Select::make('program_id')
                                    ->label('Program')
                                    ->relationship('program', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('course_type')
                                    ->label('Course Type')
                                    ->options(CourseType::class)
                                    ->required()
                                    ->live(),
                                Select::make('cognate_field_id')
                                    ->label('Cognate Field')
                                    ->options(\App\Models\CognateField::pluck('name', 'id'))
                                    ->searchable()
                                    ->hidden(function (Get $get) {
                                        $type = $get('course_type');
                                        return ($type instanceof CourseType ? $type->value : $type) !== 'cognate';
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
                                Select::make('semester_offered')
                                    ->label('Semester Offered')
                                    ->options([
                                        'First Semester' => 'First Semester',
                                        'Second Semester' => 'Second Semester',
                                        'First and Second Semester' => 'First and Second Semester',
                                        'Midyear' => 'Midyear',
                                        'First Semester, Second Semester, and Midyear' => 'First Semester, Second Semester, and Midyear',
                                    ])
                                    ->searchable(),
                                TextInput::make('units')
                                    ->label('Units')
                                    ->numeric()
                                    ->minValue(0),
                                Toggle::make('is_required')
                                    ->label('Required')
                                    ->default(true)
                                    ->columnSpanFull(),
                            ])
                    ]),
            ]);
    }
}
