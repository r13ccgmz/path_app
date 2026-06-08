<?php

namespace App\Filament\Pages\Concerns;

use App\Models\AcademicOutputCommittee;
use App\Models\Faculty;
use App\Models\Student;
use Filament\Forms;
use Filament\Tables;

trait HasAcademicOutputForm
{
    protected function getAcademicOutputForm(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Authors')
                ->schema([
                    Forms\Components\Select::make('student_id')
                        ->label('Primary Author (Owner)')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return Student::where('full_name', 'like', "%{$search}%")
                                ->orWhere('student_number', 'like', "%{$search}%")
                                ->orWhere('surname', 'like', "%{$search}%")
                                ->orWhere('given_name', 'like', "%{$search}%")
                                ->orderBy('surname')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->student_number} — {$s->surname}, {$s->given_name}"
                                ])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $s = Student::find($value);
                            return $s ? "{$s->student_number} — {$s->surname}, {$s->given_name}" : null;
                        })
                        ->required(),
                    Forms\Components\Select::make('primary_author_ids')
                        ->label('Other Primary Author(s)')
                        ->multiple()
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search, $get) {
                            $currentStudentId = $get('student_id');
                            return Student::where(function ($q) use ($search) {
                                    $q->where('student_number', 'like', "%{$search}%")
                                      ->orWhere('given_name', 'like', "%{$search}%")
                                      ->orWhere('surname', 'like', "%{$search}%");
                                })
                                ->when($currentStudentId, fn($q) => $q->where('id', '!=', $currentStudentId))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->student_number} — {$s->surname}, {$s->given_name}"
                                ]);
                        })
                        ->getOptionLabelsUsing(function (array $values) {
                            return Student::whereIn('id', $values)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->student_number} — {$s->surname}, {$s->given_name}"
                                ])
                                ->toArray();
                        })
                        ->helperText('Search and select other students who are also primary authors. They will see this output on their page.'),
                    Forms\Components\Select::make('co_author_ids')
                        ->label('Co-Author(s)')
                        ->multiple()
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search, $get) {
                            $currentStudentId = $get('student_id');
                            return Student::where(function ($q) use ($search) {
                                    $q->where('student_number', 'like', "%{$search}%")
                                      ->orWhere('given_name', 'like', "%{$search}%")
                                      ->orWhere('surname', 'like', "%{$search}%");
                                })
                                ->when($currentStudentId, fn($q) => $q->where('id', '!=', $currentStudentId))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->student_number} — {$s->surname}, {$s->given_name}"
                                ]);
                        })
                        ->getOptionLabelsUsing(function (array $values) {
                            return Student::whereIn('id', $values)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->student_number} — {$s->surname}, {$s->given_name}"
                                ])
                                ->toArray();
                        })
                        ->helperText('Search and select other students who authored this output. They will see this output as read-only on their page.')
                        ->columnSpanFull(),
                ]),
            \Filament\Schemas\Components\Section::make('Output Details')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->placeholder('Enter title of thesis, dissertation, or field study...')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options([
                            'thesis' => 'Thesis',
                            'dissertation' => 'Dissertation',
                            'field-study' => 'Field Study',
                            'others' => 'Others',
                        ])
                        ->default('thesis')
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('type_other_description')
                        ->label('Specify Other Type (e.g. Cognate, Coursework)')
                        ->visible(fn ($get) => $get('type') === 'others'),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'topic-approved' => 'Topic Approved',
                            'proposal-writing' => 'Proposal Writing',
                            'proposal-defended' => 'Proposal Defended',
                            'data-collection' => 'Data Collection',
                            'writing' => 'Writing',
                            'final-defense-scheduled' => 'Final Defense Scheduled',
                            'defended' => 'Defended',
                            'revising' => 'Revising',
                            'submitted' => 'Submitted',
                            'approved' => 'Approved',
                        ])
                        ->default('topic-approved')
                        ->required(),
                    Forms\Components\TextInput::make('drive_link')
                        ->label('Academic Output Link')
                        ->url()
                        ->placeholder('e.g. https://drive.google.com/...')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('semester_id')
                        ->label('Semester / Term')
                        ->options(
                            \App\Models\Semester::with('academicYear')
                                ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                        )
                        ->searchable()
                        ->preload()
                        ->placeholder('Select semester...')
                        ->helperText('Associate this output with a specific semester/term.'),
                    Forms\Components\TagsInput::make('keywords')
                        ->label('Keywords')
                        ->placeholder('Add keyword...')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('abstract')
                        ->label('Abstract')
                        ->placeholder('Enter the abstract here...')
                        ->rows(5)
                        ->columnSpanFull(),
                ])->columns(2),
            \Filament\Schemas\Components\Section::make('Dates')
                ->schema([
                    Forms\Components\DatePicker::make('date_submitted')
                        ->label('Date Submitted'),
                    Forms\Components\DatePicker::make('proposal_defense_date')
                        ->label('Proposal Defense Date'),
                    Forms\Components\Select::make('proposal_defense_result')
                        ->label('Proposal Defense Result')
                        ->options([
                            'passed' => 'Passed',
                            'passed-with-revisions' => 'Passed with Revisions',
                            'failed' => 'Failed',
                        ])
                        ->placeholder('—'),
                    Forms\Components\DatePicker::make('final_defense_date')
                        ->label('Final Defense Date'),
                    Forms\Components\Select::make('final_defense_result')
                        ->label('Final Defense Result')
                        ->options([
                            'passed' => 'Passed',
                            'passed-with-revisions' => 'Passed with Revisions',
                            'failed' => 'Failed',
                        ])
                        ->placeholder('—'),
                ])->columns(2)->collapsible()->collapsed(),
            \Filament\Schemas\Components\Section::make('Advisory Committee')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('committee_members')
                        ->label('Committee Members')
                        ->hiddenLabel()
                        ->schema([
                            \Filament\Schemas\Components\Group::make([
                                Forms\Components\Select::make('role')
                                    ->label('Role')
                                    ->options([
                                                'Adviser' => 'Adviser',
                                                'Co-Adviser' => 'Co-Adviser',
                                                'Former Adviser' => 'Former Adviser',
                                                'Chair' => 'Chair',
                                                'Co-Chair' => 'Co-Chair',
                                                'Cognate' => 'Cognate',
                                                'Major' => 'Major',
                                                'Minor' => 'Minor',
                                                'Member' => 'Member',
                                            ])
                                    ->required()
                                    ->columnSpan(1),
                                $this->buildFacultyIdSelect('faculty_id', 'Faculty Name')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('appointed_date')
                                    ->label('Date Appointed')
                                    ->columnSpan(1),
                            ])->columns(4),
                            \Filament\Schemas\Components\Group::make([
                                $this->buildTermSelect('term_start', 'Term Start')
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                                $this->buildTermSelect('term_end', 'Term End')
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                            ])->columns(2),
                        ])
                        ->columns(1)
                        ->columnSpanFull()
                        ->addActionLabel('Add Committee Member')
                        ->reorderable(false)
                        ->default(fn() => [
                            ['role' => 'Adviser', 'faculty_id' => null],
                        ]),
                ]),
        ];
    }



    protected function saveCommitteeMembers(\App\Models\AcademicOutput $ao, array $data): void
    {
        $ao->committeeMembers()->delete();
        if (!empty($data['committee_members'])) {
            foreach ($data['committee_members'] as $cm) {
                $facultyId = $cm['faculty_id'] ?? null;
                if ($facultyId) {
                    $faculty = \App\Models\Faculty::find($facultyId);
                    AcademicOutputCommittee::create([
                        'academic_output_id' => $ao->id,
                        'faculty_id' => $facultyId,
                        'name' => $faculty ? $faculty->full_name : '',
                        'role' => $cm['role'],
                        'appointed_date' => $cm['appointed_date'] ?? null,
                        'term_start_id' => $cm['term_start'] ?? null,
                        'term_end_id' => $cm['term_end'] ?? null,
                    ]);
                }
            }
        }
    }

    protected function getAcademicOutputTable(): array
    {
        return [
            Tables\Columns\TextColumn::make('type')
                ->label('Type')
                ->badge()
                ->sortable()
                ->color(fn ($state) => match ($state) {
                    'thesis' => 'success',
                    'dissertation' => 'warning',
                    'field-study' => 'info',
                    default => 'gray',
                })
                ->formatStateUsing(fn ($state) => ucfirst(str_replace('-', ' ', $state))),
            Tables\Columns\TextColumn::make('student.full_name')
                ->label('Student')
                ->searchable(['surname', 'given_name'])
                ->sortable()
                ->weight('bold')
                ->description(fn (\App\Models\AcademicOutput $record) => $record->student?->student_number ? \App\Models\Enrollee::formatStudentNumber($record->student->student_number) : null),
            Tables\Columns\TextColumn::make('term_code')
                ->label('Term')
                ->badge()
                ->color('info')
                ->placeholder('—')
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable()
                ->limit(60)
                ->tooltip(fn (\App\Models\AcademicOutput $record) => $record->title)
                ->wrap(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->sortable()
                ->color(fn ($state) => match ($state) {
                    'approved' => 'success',
                    'defended', 'submitted', 'proposal-defended', 'final-defense-scheduled' => 'info',
                    'topic-approved' => 'gray',
                    default => 'warning',
                })
                ->formatStateUsing(fn ($state) => ucfirst(str_replace('-', ' ', $state))),
            Tables\Columns\TextColumn::make('committeeMembers')
                ->label('Advisory Committee')
                ->getStateUsing(function ($record) {
                    $adviser = collect($record->committeeMembers)->firstWhere('role', 'Adviser');
                    if (!$adviser) {
                        $adviser = collect($record->committeeMembers)->firstWhere('role', 'adviser');
                    }
                    return $adviser ? 'Adviser: ' . $adviser->name : 'Adviser: —';
                })
                ->description(function ($record) {
                    $chair = collect($record->committeeMembers)->firstWhere('role', 'Chair');
                    if (!$chair) {
                        $chair = collect($record->committeeMembers)->firstWhere('role', 'chair');
                    }
                    return $chair ? 'Chair: ' . $chair->name : null;
                })
                ->toggleable(),
            Tables\Columns\TextColumn::make('proposal_defense_result')
                ->label('Proposal Defense')
                ->badge()
                ->sortable()
                ->color(fn ($state) => match ($state) {
                    'passed' => 'success',
                    'passed-with-revisions' => 'warning',
                    'failed' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn ($state) => $state ? ucfirst(str_replace('-', ' ', $state)) : '—')
                ->description(fn (\App\Models\AcademicOutput $record) => $record->proposal_defense_date?->format('M d, Y'))
                ->toggleable(),
            Tables\Columns\TextColumn::make('final_defense_result')
                ->label('Final Defense')
                ->badge()
                ->sortable()
                ->color(fn ($state) => match ($state) {
                    'passed' => 'success',
                    'passed-with-revisions' => 'warning',
                    'failed' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn ($state) => $state ? ucfirst(str_replace('-', ' ', $state)) : '—')
                ->description(fn (\App\Models\AcademicOutput $record) => $record->final_defense_date?->format('M d, Y'))
                ->toggleable(),
        ];
    }

    protected function getAcademicOutputFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'topic-approved' => 'Topic Approved',
                    'proposal-writing' => 'Proposal Writing',
                    'proposal-defended' => 'Proposal Defended',
                    'data-collection' => 'Data Collection',
                    'writing' => 'Writing',
                    'final-defense-scheduled' => 'Final Defense Scheduled',
                    'defended' => 'Defended',
                    'revising' => 'Revising',
                    'submitted' => 'Submitted',
                    'approved' => 'Approved',
                ]),
            Tables\Filters\SelectFilter::make('adviser')
                ->label('Adviser')
                ->options(function () {
                    return \App\Models\Faculty::orderBy('last_name')->get()->mapWithKeys(fn ($f) => [$f->full_name => $f->full_name])->toArray();
                })
                ->searchable()
                ->preload()
                ->query(function ($query, array $data) {
                    if (empty($data['value'])) return $query;
                    return $query->whereHas('committeeMembers', function ($q) use ($data) {
                        $q->whereIn('role', ['Adviser', 'adviser'])->where('name', $data['value']);
                    });
                }),
            Tables\Filters\SelectFilter::make('proposal_defense_result')
                ->label('Proposal Result')
                ->options(['passed' => 'Passed', 'passed-with-revisions' => 'Passed w/ Revisions', 'failed' => 'Failed']),
            Tables\Filters\SelectFilter::make('final_defense_result')
                ->label('Final Result')
                ->options(['passed' => 'Passed', 'passed-with-revisions' => 'Passed w/ Revisions', 'failed' => 'Failed']),
            Tables\Filters\SelectFilter::make('semester_id')
                ->label('Semester / Term')
                ->options(
                    \App\Models\Semester::with('academicYear')
                        ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                        ->get()
                        ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                )
                ->searchable()
                ->preload(),
        ];
    }

    protected function getAcademicOutputTableActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->form($this->getAcademicOutputForm())
                ->mutateRecordDataUsing(function (array $data, \App\Models\AcademicOutput $record): array {
                    $arguments = $data;
                    $arguments['primary_author_ids'] = collect($record->primaryAuthors)
                        ->where('id', '!=', $record->student_id ?? 0)
                        ->pluck('id')->toArray();
                    $arguments['co_author_ids'] = collect($record->coAuthors)->pluck('id')->toArray();
                    
                    $arguments['committee_members'] = $record->committeeMembers->map(fn($cm) => [
                        'role' => $cm->role,
                        'faculty_id' => $cm->faculty_id,
                        'appointed_date' => $cm->appointed_date?->format('Y-m-d'),
                        'term_start' => $cm->term_start_id ?? null,
                        'term_end' => $cm->term_end_id ?? null,
                    ])->toArray();
                    
                    return $arguments;
                })
                ->using(function (array $data, \App\Models\AcademicOutput $record): \App\Models\AcademicOutput {
                    $aoData = collect($data)->only([
                        'student_id', 'semester_id', 'title', 'type', 'type_other_description', 'drive_link', 'status', 
                        'proposal_defense_date', 'proposal_defense_result', 'final_defense_date', 'final_defense_result', 'date_submitted',
                        'keywords', 'abstract'
                    ])->toArray();

                    // Auto-sync term_code from selected semester
                    if (!empty($aoData['semester_id'])) {
                        $semester = \App\Models\Semester::find($aoData['semester_id']);
                        $aoData['term_code'] = $semester?->term_code;
                    } else {
                        $aoData['term_code'] = null;
                    }
                    $record->update($aoData);
                    
                    $this->saveCommitteeMembers($record, $data);
                    
                    // Sync co-authors and primary authors via pivot table
                    $coAuthorIds = $data['co_author_ids'] ?? [];
                    $primaryAuthorIds = $data['primary_author_ids'] ?? [];
                    
                    $pivotData = [];
                    if (!empty($data['student_id'])) {
                        $pivotData[(int) $data['student_id']] = ['role' => 'primary_author'];
                    }
                    foreach ($primaryAuthorIds as $pId) {
                        $pivotData[(int) $pId] = ['role' => 'primary_author'];
                    }
                    foreach ($coAuthorIds as $coAuthorId) {
                        $pivotData[(int) $coAuthorId] = ['role' => 'co_author'];
                    }
                    $record->students()->sync($pivotData);
                    
                    return $record;
                })
                ->modalWidth('4xl')
                ->modalHeading('Edit Academic Output'),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}

