<?php

namespace App\Filament\Pages\StudentHistory;

use App\Models\AcademicOutput;
use App\Models\AcademicOutputCommittee;
use App\Models\Course;
use App\Models\Enrollee;
use App\Models\EnrollmentCourse;
use App\Models\Faculty;
use App\Models\Graduate;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProgram;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * HasStudentActions trait extracted from StudentHistory.
 */
trait HasStudentActions
{

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('backToList')
                ->label('Back to All Students')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->action(fn() => $this->backToList())
                ->visible(fn () => !empty($this->studentInfo)),

            \Filament\Actions\ActionGroup::make([
                \Filament\Actions\Action::make('downloadPdf')
                    ->label('Download PDF Report')
                    ->icon('heroicon-m-document-text')
                    ->color('danger')
                    ->action(fn() => $this->downloadPdf()),
                \Filament\Actions\Action::make('downloadDetailedPdf')
                    ->label('Download PDF (with Grades)')
                    ->icon('heroicon-m-document-magnifying-glass')
                    ->color('warning')
                    ->action(fn() => $this->downloadDetailedPdf()),
                \Filament\Actions\Action::make('downloadXls')
                    ->label('Download Excel (XLSX)')
                    ->icon('heroicon-m-table-cells')
                    ->color('success')
                    ->action(fn() => $this->downloadXls()),
                \Filament\Actions\Action::make('downloadSummaryXls')
                    ->label('Download Summary Excel')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->color('info')
                    ->action(fn() => $this->downloadSummaryXls()),
            ])
            ->label('Export Record')
            ->button()
            ->color('primary')
            ->icon('heroicon-m-arrow-down-tray')
            ->visible(fn () => !empty($this->studentInfo)),

            \Filament\Actions\ActionGroup::make([

            // ── Edit Student Info (Filament Modal) ──
            \Filament\Actions\Action::make('editStudentInfo')
                ->label('Edit Student Info')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->modalHeading('Edit Student Information')
                ->modalWidth('4xl')
                ->visible(fn () => !empty($this->studentInfo) && !auth()->user()->hasRole('viewer'))
                ->fillForm(function (): array {
                    $student = $this->getStudentRecord();
                    if (!$student) return [];
                    return [
                        'surname' => $student->surname,
                        'given_name' => $student->given_name,
                        'middle_name' => $student->middle_name,
                        'email' => $student->email,
                        'up_email' => $student->up_email,
                        'alternative_email' => $student->alternative_email,
                        'sex' => $student->sex,
                        'birthdate' => $student->birthdate?->format('Y-m-d'),
                        'nationality' => $student->nationality,
                        'marital_status' => $student->marital_status,
                        'contact_number' => $student->contact_number,
                        'primary_contact_number' => $student->primary_contact_number,
                        'alternative_contact_number' => $student->alternative_contact_number,
                        'address' => $student->address,
                        'country_of_origin' => $student->country_of_origin,
                        'social_facebook' => $student->social_facebook,
                        'social_linkedin' => $student->social_linkedin,
                        'social_other' => $student->social_other,
                        'institution_affiliated' => $student->institution_affiliated,
                        'student_status' => $student->student_status,
                        'applicant_status' => $student->applicant_status,
                        'admission_semester_id' => $student->admission_semester_id,
                        'admission_date' => $student->admission_date?->format('Y-m-d'),
                        'student_number' => $student->student_number,
                        'student_number' => $student->student_number,
                        'registration_adviser_id' => $student->registration_adviser_id,
                        'registration_adviser_appointed_date' => $student->registration_adviser_appointed_date?->format('Y-m-d'),
                        'program_major_id' => $student->program_major_id,
                        
                        // Committee members
                        'committee_members' => $student->committeeMembers->map(fn($cm) => [
                            'role' => $cm->role,
                            'faculty_id' => $cm->faculty_id,
                            'appointed_date' => $cm->appointed_date?->format('Y-m-d'),
                            'term_start_id' => $cm->term_start_id,
                            'term_end_id' => $cm->term_end_id,
                        ])->toArray(),
                    ];
                })
                ->form([
                    \Filament\Schemas\Components\Section::make('Personal Information')
                        ->collapsible()
                        ->schema([
                            Forms\Components\TextInput::make('student_number')
                                ->label('Student Number')
                                ->required()
                                ->maxLength(20),
                            Forms\Components\TextInput::make('surname')
                                ->label('Last Name')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('given_name')
                                ->label('First Name')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('middle_name')
                                ->label('Middle Name')
                                ->maxLength(100),
                            Forms\Components\Select::make('student_status')
                                ->label('Student Status')
                                ->options([
                                    'active' => 'Active',
                                    'candidate' => 'Candidate for Graduation',
                                    'on-leave' => 'On Leave',
                                    'leave-of-absence-approved' => 'Leave of Absence Approved',
                                    'absent-without-official-leave' => 'Absent Without Official Leave',
                                    'graduated' => 'Graduated',
                                    'dismissed' => 'Dismissed',
                                    'dropped' => 'Dropped',
                                    'withdrawn' => 'Withdrawn',
                                    'inactive' => 'Inactive',
                                ])
                                ->columnSpanFull(),
                            \Filament\Schemas\Components\Html::make('<hr class="border-gray-200 dark:border-gray-700 my-2">')->columnSpanFull(),
                            Forms\Components\Select::make('sex')
                                ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),
                            Forms\Components\DatePicker::make('birthdate'),
                            Forms\Components\Select::make('nationality')
                                ->label('Nationality(s)')
                                ->options(\App\Models\Student::getNationalities())
                                ->multiple()
                                ->searchable(),
                            Forms\Components\Select::make('marital_status')
                                ->options(['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'separated' => 'Separated', 'divorced' => 'Divorced']),
                            Forms\Components\Select::make('country_of_origin')
                                ->label('Country of Origin')
                                ->options(\App\Models\Student::getCountries())
                                ->searchable(),
                            Forms\Components\TagsInput::make('address')
                                ->label('Address(es)')
                                ->placeholder('Add address...')
                                ->columnSpanFull(),
                        ])->columns(2),
                    \Filament\Schemas\Components\Section::make('Contact Details')
                        ->description('Email addresses and phone numbers.')
                        ->collapsed()
                        ->schema([
                            Forms\Components\TextInput::make('up_email')
                                ->label('UP Email')
                                ->email()
                                ->placeholder('student@up.edu.ph')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->label('Primary Email')
                                ->email()
                                ->maxLength(255),
                            Forms\Components\TagsInput::make('alternative_email')
                                ->label('Alternative Email(s)')
                                ->placeholder('Add alternative email'),
                            Forms\Components\TextInput::make('primary_contact_number')
                                ->label('Primary Contact Number')
                                ->tel()
                                ->maxLength(20),
                            Forms\Components\TagsInput::make('alternative_contact_number')
                                ->label('Alternative Contact Number(s)')
                                ->placeholder('Add alternative number'),
                        ])->columns(3),
                    \Filament\Schemas\Components\Section::make('Social Media & Affiliation')
                        ->collapsible()
                        ->collapsed()
                        ->description('Social media profiles and institutional affiliations.')
                        ->schema([
                            Forms\Components\TextInput::make('social_facebook')
                                ->label('Facebook')
                                ->placeholder('Profile URL or name')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('social_linkedin')
                                ->label('LinkedIn')
                                ->placeholder('Profile URL')
                                ->maxLength(255),
                            Forms\Components\TagsInput::make('social_other')
                                ->label('Other Social Media / Contact Handles')
                                ->placeholder('Add handle...'),
                            Forms\Components\TagsInput::make('institution_affiliated')
                                ->label('Office / School / Institution Affiliated')
                                ->placeholder('Add affiliation...'),
                        ])->columns(2),
                    \Filament\Schemas\Components\Section::make('Admission Details')
                        ->collapsible()
                        ->collapsed()
                        ->description('Admission status.')
                        ->schema([
                            Forms\Components\Select::make('applicant_status')
                                ->label('Applicant Status')
                                ->options([
                                    'regular' => 'Regular',
                                    'probationary' => 'Probationary',
                                    'denied' => 'Denied',
                                    'change-of-program' => 'Change of Program',
                                    'deferred' => 'Deferred',
                                ])
                                ->placeholder('Select...'),
                            Forms\Components\Select::make('admission_semester_id')
                                ->label('Admission Semester')
                                ->options(function () {
                                    return Semester::with('academicYear')
                                        ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                                        ->get()
                                        ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                                        ->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select semester...'),
                            Forms\Components\DatePicker::make('admission_date')
                                ->label('Admission Date'),
                            \Filament\Schemas\Components\Html::make('<hr class="border-gray-200 dark:border-gray-700 my-2">')->columnSpanFull(),
                            $this->buildAdviserRow('registration_adviser', 'Registration Adviser'),
                        ])->columns(2),
                    \Filament\Schemas\Components\Section::make('Advisory Committee')
                        ->collapsible()
                        ->collapsed()
                        ->description('The student\'s current guidance committee.')
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
                                        $this->buildTermSelect('term_start_id', 'Term Start')->columnSpan(1),
                                        $this->buildTermSelect('term_end_id', 'Term End')->columnSpan(1),
                                    ])->columns(2),
                                ])
                                ->columns(1)
                                ->columnSpanFull()
                                ->addActionLabel('Add Committee Member')
                                ->reorderable(false),
                        ]),
                ])
                ->action(function (array $data): void {
                    $student = $this->getStudentRecord();
                    if (!$student) return;

                    $fullName = Student::buildFullName($data['surname'], $data['given_name'], $data['middle_name'] ?? null);
                    
                    // Cascade update for student number
                    $newNumber = $data['student_number'] ?? $student->student_number;
                    if ($newNumber !== $student->student_number) {
                        if (Student::where('student_number', $newNumber)->exists()) {
                            Notification::make()->title('Student Number already exists')->danger()->send();
                            return;
                        }
                        
                        DB::table('enrollees')->where('student_number', $student->student_number)->update(['student_number' => $newNumber]);
                        DB::table('graduates')->where('student_number', $student->student_number)->update(['student_number' => $newNumber]);
                        
                        $this->studentNumber = $newNumber; // update component state
                    }

                    $studentData = \Illuminate\Support\Arr::except($data, ['committee_members']);

                    if (array_key_exists('email', $studentData) && $studentData['email'] === null) {
                        $studentData['email'] = '';
                    }

                    $student->update(array_merge($studentData, ['full_name' => $fullName]));
                    
                    // Sync committee members
                    $student->committeeMembers()->delete();
                    if (!empty($data['committee_members'])) {
                        foreach ($data['committee_members'] as $cm) {
                            if ($cm['faculty_id']) {
                                $student->committeeMembers()->create([
                                    'faculty_id' => $cm['faculty_id'],
                                    'role' => $cm['role'],
                                    'appointed_date' => $cm['appointed_date'] ?? null,
                                    'term_start_id' => $cm['term_start_id'] ?? null,
                                    'term_end_id' => $cm['term_end_id'] ?? null,
                                ]);
                            }
                        }
                    }

                    // Refresh displayed studentInfo by re-triggering search
                    $this->search();

                    Notification::make()
                        ->title('Student Info Updated')
                        ->success()
                        ->duration(3000)
                        ->send();

                    // Check for advisory conflict
                    $activeAdviserCount = $student->committeeMembers()
                        ->where('role', 'Adviser')
                        ->whereNull('term_end_id')
                        ->count();

                    if ($activeAdviserCount > 1) {
                        Notification::make()
                            ->title('Advisory Conflict: Multiple Primary Advisers')
                            ->body("This student currently has {$activeAdviserCount} active Primary Advisers. Please update their roles to resolve this tracking conflict.")
                            ->warning()
                            ->persistent()
                            ->send();
                    }
                }),

            // ── Add Graduate Info (for non-graduates) ──
            \Filament\Actions\Action::make('addGraduateInfo')
                ->label('Add Graduation Info')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->modalHeading('Add Graduation Information')
                ->modalWidth('4xl')
                ->visible(fn () => !empty($this->studentInfo) && !auth()->user()->hasRole('viewer'))
                ->fillForm(function (): array {
                    // Pre-fill from student's existing data
                    $student = Student::with(['committeeMembers.faculty', 'studentPrograms.program'])->where('student_number', $this->studentNumber)->first();
                    $sp = $student ? $student->studentPrograms->first() : null;

                    return [
                        'program_id' => $sp?->program_id,
                        'program_search' => $sp?->program_id,
                        'degree' => $sp?->program?->code ?? '',
                        'program_name' => $sp?->program?->name ?? '',
                        'major' => '',
                        'country_of_origin' => $student?->country_of_origin ? ucwords(strtolower($student->country_of_origin)) : '',
                        'committee_data' => collect($student?->committeeMembers ?? [])->map(fn($cm) => [
                            'role' => $cm->role,
                            'faculty_id' => $cm->faculty_id,
                            'appointed_date' => $cm->appointed_date?->format('Y-m-d'),
                            'term_start' => $cm->term_start_id ?? null,
                            'term_end' => $cm->term_end_id ?? null,
                        ])->toArray(),
                    ];
                })
                ->form([
                    \Filament\Schemas\Components\Section::make('Graduation Details')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Select::make('semester_graduated')
                                ->label('Semester Graduated')
                                ->options(function () {
                                    return Semester::with('academicYear')
                                        ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                                        ->get()
                                        ->mapWithKeys(fn ($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
                                        ->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select semester...'),
                            Forms\Components\Select::make('program_search')
                                ->label('Select from System Programs')
                                ->options(\App\Models\Program::all()->mapWithKeys(fn($p) => [$p->id => "{$p->code} — {$p->name}"]))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $program = \App\Models\Program::find($state);
                                    if ($program) {
                                        $set('degree', $program->code);
                                        $set('program_name', $program->name);
                                        $set('program_id', $program->id);
                                        $set('major', null);
                                    }
                                })
                                ->columnSpanFull()
                                ->helperText('Select a program to automatically fill the Degree and Program below, or leave blank to enter manually.')
                                ->dehydrated(false),
                            Forms\Components\Hidden::make('program_id'),
                            Forms\Components\Select::make('degree')
                                ->label('Degree')
                                ->options(\App\Models\Program::pluck('code', 'code')->toArray())
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $program = \App\Models\Program::where('code', $state)->first();
                                        if ($program) {
                                            $set('program_name', $program->name);
                                            $set('program_id', $program->id);
                                            $set('major', null);
                                        }
                                    }
                                })
                                ->placeholder('e.g. MDMG, PhD-DVST'),
                            Forms\Components\Select::make('program_name')
                                ->label('Program')
                                ->options(\App\Models\Program::all()->mapWithKeys(fn($p) => [$p->name => $p->name]))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $program = \App\Models\Program::where('name', $state)->first();
                                        if ($program) {
                                            $set('degree', $program->code);
                                            $set('program_id', $program->id);
                                            $set('major', null);
                                        }
                                    }
                                })
                                ->placeholder('Select program...'),
                            Forms\Components\Select::make('major')
                                ->label('Specialization')
                                ->options(function (callable $get) {
                                    $programId = $get('program_id');
                                    if ($programId) {
                                        $majors = \App\Models\ProgramMajor::where('program_id', $programId)
                                            ->orderBy('name')->pluck('name', 'name')->toArray();
                                        if (!empty($majors)) {
                                            return $majors;
                                        }
                                    }
                                    return [];
                                })
                                ->searchable()
                                ->placeholder('Select specialization (if applicable)...')
                                ->nullable(),
                            Forms\Components\Select::make('country_of_origin')
                                ->label('Country of Origin')
                                ->options(\App\Models\Student::getCountries())
                                ->searchable(),
                        ])->columns(2),
                    \Filament\Schemas\Components\Section::make('Advisory Committee')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            Forms\Components\Repeater::make('committee_data')
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
                                ->reorderable(false),
                        ]),
                ])
                ->action(function (array $data): void {
                    $committeeData = $data['committee_data'] ?? [];
                    $data['chair'] = '';
                    $data['co_chair'] = '';
                    $data['member1'] = '';
                    $data['member2'] = '';
                    $data['member3'] = '';
                    $data['member4'] = '';
                    $data['member5'] = '';
                    $mCount = 1;
                    foreach($committeeData as $member) {
                        $facultyId = $member['faculty_id'] ?? null;
                        $faculty = $facultyId ? \App\Models\Faculty::find($facultyId) : null;
                        $memberName = $faculty ? $faculty->full_name : '';
                        $role = $member['role'] ?? 'Member';

                        $roleLower = strtolower($role);
                        if (str_contains($roleLower, 'chair') && !str_contains($roleLower, 'co-chair') && !$data['chair']) {
                            $data['chair'] = $memberName;
                        } elseif (str_contains($roleLower, 'co-chair') && !$data['co_chair']) {
                            $data['co_chair'] = $memberName;
                        } else {
                            if ($mCount <= 5) {
                                $data["member{$mCount}"] = $memberName;
                                $mCount++;
                            }
                        }
                    }

                    $data['student_number'] = $this->studentNumber;
                    $data['name'] = $this->studentInfo['name'] ?? '';
                    $data['match_type'] = 'manual';
                    $data['source'] = 'manual';
                    
                    unset($data['committee_data']);
                    $graduate = Graduate::create($data);

                    // Sync to normalized graduate_committee_members table
                    foreach($committeeData as $member) {
                        $facultyId = $member['faculty_id'] ?? null;
                        if ($facultyId) {
                            $faculty = \App\Models\Faculty::find($facultyId);
                            \App\Models\GraduateCommitteeMember::create([
                                'graduate_id' => $graduate->id,
                                'faculty_id' => $facultyId,
                                'name' => $faculty ? $faculty->full_name : '',
                                'role' => $member['role'] ?? 'Member',
                                'match_type' => 'manual',
                                'appointed_date' => $member['appointed_date'] ?? null,
                                'term_start_id' => $member['term_start'] ?? null,
                                'term_end_id' => $member['term_end'] ?? null,
                            ]);
                        }
                    }

                    // Sync core student record
                    $student = $this->getStudentRecord();
                    if ($student) {
                        $semId = null;
                        if (!empty($data['semester_graduated'])) {
                            $semId = \App\Models\Semester::where('term_code', $data['semester_graduated'])->value('id');
                        }
                        
                        $student->update([
                            'student_status' => 'graduated',
                            'graduation_semester_id' => $semId,
                        ]);
                    }

                    // Refresh displayed studentInfo by re-triggering search
                    $this->search();

                    Notification::make()
                        ->title('Graduation Info Added')
                        ->success()
                        ->duration(3000)
                        ->send();
                }),

            \Filament\Actions\Action::make('addProgramEnrollment')
                ->label('Add Enrollment Record')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->size('sm')
                ->modalHeading('Add Enrollment Record')
                ->modalDescription('Select one or more courses and a term. Manual entries will be flagged separately from official GS data.')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Add')
                ->visible(fn () => !empty($this->studentInfo) && !auth()->user()->hasRole('viewer'))
                ->form([
                    \Filament\Forms\Components\Select::make('enrollment_status')
                        ->label('Enrollment Status')
                        ->options([
                            'Auto' => 'Auto (Calculate from Units)',
                            'Full-Time' => 'Full-Time',
                            'Part-Time' => 'Part-Time',
                        ])
                        ->default('Auto')
                        ->required(),
                    \Filament\Forms\Components\Repeater::make('course_units')
                        ->label('Courses & Units')
                        ->schema([
                            \Filament\Forms\Components\Select::make('course_id')
                                ->label('Course')
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                    if ($state) {
                                        $course = Course::find($state);
                                        if ($course) {
                                            $units = $this->estimateCourseUnits($course->course_code);
                                            $set('units', $units);
                                        }
                                    }
                                })
                                ->getSearchResultsUsing(function (string $search) {
                                    return Course::where('course_code', 'like', "%{$search}%")
                                        ->orWhere('course_name', 'like', "%{$search}%")
                                        ->orderBy('course_code')
                                        ->limit(30)
                                        ->get()
                                        ->mapWithKeys(fn ($c) => [
                                            $c->id => "{$c->course_code} — {$c->course_name}"
                                        ]);
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    $c = Course::find($value);
                                    return $c ? "{$c->course_code} — {$c->course_name}" : $value;
                                })
                                ->createOptionUsing(function (array $data, \Filament\Schemas\Components\Utilities\Set $set) {
                                    $code = strtoupper(trim($data['course_code']));
                                    $course = Course::firstOrCreate(
                                        ['course_code' => $code],
                                        [
                                            'course_name' => $this->generatePlaceholderName($code),
                                            'is_active' => false,
                                        ]
                                    );
                                    $set('units', $this->estimateCourseUnits($code));
                                    return $course->id;
                                })
                                ->createOptionForm([
                                    \Filament\Forms\Components\TextInput::make('course_code')
                                        ->label('New Course Code')
                                        ->required()
                                        ->placeholder('e.g. DM 201'),
                                ])
                                ->required()
                                ->columnSpan(2),
                            \Filament\Forms\Components\TextInput::make('units')
                                ->label('Units')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->default(3)
                                ->columnSpan(1),
                        ])
                        ->columns(3)
                        ->addActionLabel('Add Course')
                        ->required()
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable(false)
                        ->helperText('Select courses and adjust units if necessary (e.g. partial units for thesis).'),
                    \Filament\Forms\Components\Select::make('student_program_id')
                        ->label('Program')
                        ->options(function () {
                            $student = $this->getStudentRecord();
                            if (!$student) return [];
                            return \App\Models\StudentProgram::where('student_id', $student->id)
                                ->with('program')
                                ->get()
                                ->mapWithKeys(function ($sp) {
                                    $progName = $sp->program ? $sp->program->name : ($sp->raw_degree_name ?? 'Unknown Program');
                                    return [$sp->id => "{$progName} (" . ucfirst($sp->status) . ")"];
                                })
                                ->toArray();
                        })
                        ->createOptionForm([
                            \Filament\Forms\Components\Select::make('program_id')
                                ->label('New Program')
                                ->options(Program::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->placeholder('Select a program...'),
                            \Filament\Forms\Components\Select::make('program_major_id')
                                ->label('Specialization / Major')
                                ->options(function (callable $get) {
                                    $programId = $get('program_id');
                                    if (!$programId) return [];
                                    return \App\Models\ProgramMajor::where('program_id', $programId)
                                        ->orderBy('name')->pluck('name', 'id')->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select specialization...')
                                ->nullable(),
                        ])
                        ->createOptionUsing(function (array $data): string {
                            $student = $this->getStudentRecord();
                            if (!$student) return '';
                            $sp = \App\Models\StudentProgram::firstOrCreate(
                                [
                                    'student_id' => $student->id,
                                    'program_id' => $data['program_id'],
                                    'program_major_id' => $data['program_major_id'] ?? null,
                                ],
                                [
                                    'status' => 'active',
                                ]
                            );

                            return (string) $sp->id;
                        })
                        ->required()
                        ->placeholder('Select program or click "+" to add new...'),
                    \Filament\Forms\Components\Select::make('term_code')
                        ->label('Term')
                        ->options(function () {
                            return Semester::with('academicYear')
                                ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
                                ->toArray();
                        })
                        ->searchable()
                        ->required()
                        ->placeholder('Select semester...'),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'enrolled' => 'Enrolled',
                            'completed' => 'Completed',
                            'in-progress' => 'In Progress',
                            'incomplete' => 'Incomplete',
                            'dropped' => 'Dropped',
                            'withdrawn' => 'Withdrawn',
                        ])
                        ->default('enrolled')
                        ->required(),
                    
                ])
                ->action(function (array $data): void {
                    $student = $this->getStudentRecord();
                    if (!$student) return;

                    $courseUnits = $data['course_units'] ?? [];
                    if (empty($courseUnits)) return;

                    // Find semester by term code
                    $semesterId = null;
                    if (!empty($data['term_code'])) {
                        $semesterId = DB::table('semesters')
                            ->where('term_code', trim($data['term_code']))
                            ->value('id');
                    }

                    $studentProgramId = $data['student_program_id'];
                    $addedCodes = [];
                    $totalUnitsAdded = 0;

                    // Automatically update the student's primary program if it's not set
                    $sp = \App\Models\StudentProgram::find($studentProgramId);
                    if ($sp && !$student->program_id) {
                        $student->update(['program_id' => $sp->program_id]);
                    }

                    foreach ($courseUnits as $item) {
                        $course = Course::find($item['course_id']);
                        if (!$course) continue;

                        $units = (int) ($item['units'] ?? 0);

                        StudentEnrollment::create([
                            'student_id' => $student->id,
                            'student_program_id' => $studentProgramId,
                            'course_id' => $course->id,
                            'semester_id' => $semesterId,
                            'status' => $data['status'],
                            'units_earned' => $units,
                            'source' => 'manual',
                        ]);

                        $addedCodes[] = $course->course_code;
                        $totalUnitsAdded += $units;
                    }

                    // Sync to Enrollee so it appears in the Enrollment History table
                    if (!empty($data['term_code'])) {
                        $enrollee = Enrollee::where('student_number', $student->student_number)
                            ->where('term_id', $data['term_code'])
                            ->first();

                        if ($enrollee) {
                            $existingCourses = array_map('trim', explode(',', $enrollee->courses_enrolled ?? ''));
                            $newCodes = array_diff($addedCodes, $existingCourses);
                            if (!empty($newCodes)) {
                                $enrollee->courses_enrolled = trim(($enrollee->courses_enrolled ?? '') . ', ' . implode(', ', $newCodes), ', ');
                                $enrollee->total_units += $totalUnitsAdded;
                                if (($data['enrollment_status'] ?? 'Auto') !== 'Auto') {
                                    $enrollee->enrollment_status = $data['enrollment_status'];
                                }
                                $enrollee->save();
                            }
                        } else {
                            $baseEnrollee = Enrollee::where('student_number', $student->student_number)->latest('term_id')->first();
                            $sp = \App\Models\StudentProgram::with(['program', 'programMajor'])->find($studentProgramId);

                            $lastName = $baseEnrollee?->last_name ?? $student->surname ?? 'Unknown';
                            $firstName = $baseEnrollee?->first_name ?? $student->given_name ?? 'Unknown';

                            $degProgName = null;
                            if ($sp?->program) {
                                $degProgName = $sp->program->name;
                                if ($sp->programMajor) {
                                    $degProgName .= ' in ' . $sp->programMajor->name;
                                }
                            } else {
                                $degProgName = $baseEnrollee?->degree_program;
                            }

                            $enrollee = Enrollee::create([
                                'term_id' => $data['term_code'],
                                'student_number' => $student->student_number,
                                'last_name' => $lastName,
                                'first_name' => $firstName,
                                'degree_program' => $degProgName,
                                'program_id' => $sp?->program_id ?? $baseEnrollee?->program_id,
                                'program_major_id' => $sp?->program_major_id ?? $baseEnrollee?->program_major_id,
                                'courses_enrolled' => implode(', ', $addedCodes),
                                'total_units' => $totalUnitsAdded,
                                'source' => 'manual',
                                'campus_id' => $baseEnrollee?->campus_id,
                                'sex' => $baseEnrollee?->sex ?? $student->sex,
                                'nationality' => $baseEnrollee?->nationality ?? (is_array($student->nationality) ? implode(', ', $student->nationality) : $student->nationality),
                                'email' => $baseEnrollee?->email ?? $student->email,
                                'marital_status' => $baseEnrollee?->marital_status ?? $student->marital_status,
                                'birthdate' => $baseEnrollee?->birthdate ?? $student->birthdate,
                                'enrollment_status' => ($data['enrollment_status'] ?? 'Auto') !== 'Auto' ? $data['enrollment_status'] : null,
                            ]);
                        }

                        // Sync courses to pivot table so badges appear in the Enrollees list
                        if ($enrollee) {
                            $pivotCourseIds = [];
                            foreach ($addedCodes as $code) {
                                $ec = EnrollmentCourse::firstOrCreate(['course_code' => $code]);
                                $pivotCourseIds[] = $ec->id;
                            }
                            if (!empty($pivotCourseIds)) {
                                $enrollee->enrollmentCourses()->syncWithoutDetaching($pivotCourseIds);
                            }
                        }
                    }

                    $courseList = implode(', ', $addedCodes);

                    // If student is currently graduated, prompt to reactivate
                    if ($student->student_status === 'graduated') {
                        Notification::make()
                            ->title('Student Status: Graduated')
                            ->body("This student is currently marked as graduated. New enrollment added for {$courseList}. Would you like to set the student status back to active?")
                            ->warning()
                            ->persistent()
                            ->actions([
                                \Filament\Actions\Action::make('reactivate')
                                    ->label('Set to Active')
                                    ->button()
                                    ->color('success')
                                    ->dispatch('reactivateStudent', ['studentNumber' => $student->student_number])
                                    ->close(),
                                \Filament\Actions\Action::make('keep_graduated')
                                    ->label('Keep as Graduated')
                                    ->button()
                                    ->color('gray')
                                    ->close(),
                            ])
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Enrollment Record' . (count($addedCodes) > 1 ? 's' : '') . ' Added')
                            ->body("{$courseList} manually added to records")
                            ->success()
                            ->duration(3000)
                            ->send();
                    }
                }),

            // ── Add/Edit Academic Output (Filament Modal) ──
            \Filament\Actions\Action::make('addAcademicOutput')
                ->label('Add Academic Output')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->size('sm')
                ->modalHeading(fn () => $this->editingAoId ? 'Edit Academic Output' : 'Add Academic Output')
                ->modalWidth('3xl')
                ->modalSubmitActionLabel(fn () => $this->editingAoId ? 'Update' : 'Add')
                ->visible(fn () => !empty($this->studentInfo) && !auth()->user()->hasRole('viewer'))
                ->mountUsing(function ($form, array $arguments) {
                    if (empty($arguments)) {
                        $this->editingAoId = null;
                        $form->fill([
                            'type' => 'thesis',
                            'status' => 'topic-approved',
                        ]);
                    } else {
                        // Extract primary_author_ids and co_author_ids from the existing relationship
                        if (isset($arguments['id'])) {
                            $ao = AcademicOutput::with('students')->find($arguments['id']);
                            if ($ao) {
                                $arguments['primary_author_ids'] = $ao->primaryAuthors()
                                    ->where('student_id', '!=', $this->getStudentRecord()->id ?? 0)
                                    ->pluck('student_id')->toArray();
                                $arguments['co_author_ids'] = $ao->coAuthors()->pluck('student_id')->toArray();
                            }
                        }
                        $form->fill($arguments);
                    }
                })
                ->form([
                    \Filament\Schemas\Components\Section::make('Authors')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Select::make('primary_author_ids')
                                ->label('Other Primary Author(s)')
                                ->multiple()
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search) {
                                    $currentStudent = $this->getStudentRecord();
                                    return Student::where(function ($q) use ($search) {
                                            $q->where('student_number', 'like', "%{$search}%")
                                              ->orWhere('given_name', 'like', "%{$search}%")
                                              ->orWhere('surname', 'like', "%{$search}%");
                                        })
                                        ->when($currentStudent, fn($q) => $q->where('id', '!=', $currentStudent->id))
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
                                ->getSearchResultsUsing(function (string $search) {
                                    $currentStudent = $this->getStudentRecord();
                                    return Student::where(function ($q) use ($search) {
                                            $q->where('student_number', 'like', "%{$search}%")
                                              ->orWhere('given_name', 'like', "%{$search}%")
                                              ->orWhere('surname', 'like', "%{$search}%");
                                        })
                                        ->when($currentStudent, fn($q) => $q->where('id', '!=', $currentStudent->id))
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
                        ->collapsible()
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
                                ->placeholder('Select semester...'),
                            Forms\Components\Textarea::make('abstract')
                                ->label('Abstract')
                                ->rows(4)
                                ->columnSpanFull()
                                ->maxLength(5000),
                            Forms\Components\TagsInput::make('keywords')
                                ->label('Keywords')
                                ->separator(',')
                                ->placeholder('Add keywords...')
                                ->columnSpanFull(),
                        ])->columns(2),
                    \Filament\Schemas\Components\Section::make('Dates')
                        ->collapsible()
                        ->collapsed()
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
                        ])->columns(2)->collapsible(),
                    \Filament\Schemas\Components\Section::make('Advisory Committee')
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
                                            ->label('Appointed Date')
                                            ->columnSpan(1),
                                    ])->columns(4),
                                    \Filament\Schemas\Components\Group::make([
                                        $this->buildTermSelect('term_start_id', 'Term Start')->columnSpan(1),
                                        $this->buildTermSelect('term_end_id', 'Term End')->columnSpan(1),
                                    ])->columns(2),
                                ])
                                ->columns(1)
                                ->columnSpanFull()
                                ->addActionLabel('Add Committee Member')
                                ->reorderable(false)
                                ->default(fn() => [
                                    ['role' => 'Adviser', 'name' => ''],
                                ]),
                        ])->collapsible(),
                    
                ])
                ->action(function (array $data): void {
                    $student = $this->getStudentRecord();
                    if (!$student) return;

                    $aoData = [
                        'student_id' => $student->id,
                        'title' => $data['title'],
                        'type' => $data['type'],
                        'type_other_description' => $data['type_other_description'] ?? null,
                        'abstract' => $data['abstract'] ?? null,
                        'keywords' => $data['keywords'] ?? null,
                        'drive_link' => $data['drive_link'] ?? null,
                        'status' => $data['status'],
                        'semester_id' => $data['semester_id'] ?? null,
                        'proposal_defense_date' => $data['proposal_defense_date'] ?? null,
                        'proposal_defense_result' => $data['proposal_defense_result'] ?? null,
                        'final_defense_date' => $data['final_defense_date'] ?? null,
                        'final_defense_result' => $data['final_defense_result'] ?? null,
                        'date_submitted' => $data['date_submitted'] ?? null,
                    ];

                    // Auto-sync term_code from selected semester
                    if (!empty($aoData['semester_id'])) {
                        $semester = \App\Models\Semester::find($aoData['semester_id']);
                        $aoData['term_code'] = $semester?->term_code;
                    } else {
                        $aoData['term_code'] = null;
                    }

                    if ($this->editingAoId) {
                        $ao = AcademicOutput::where('id', $this->editingAoId)
                            ->where('student_id', $student->id)
                            ->first();
                        if ($ao) $ao->update($aoData);
                        $msg = 'Academic Output Updated';
                    } else {
                        $ao = AcademicOutput::create($aoData);
                        $msg = 'Academic Output Added';
                    }

                    // Sync committee members
                    if ($ao) {
                        $ao->committeeMembers()->delete();
                        if (!empty($data['committee_members'])) {
                            foreach ($data['committee_members'] as $cm) {
                                $facultyId = $cm['faculty_id'] ?? null;
                                if (!empty($facultyId)) {
                                    $faculty = \App\Models\Faculty::find($facultyId);
                                    AcademicOutputCommittee::create([
                                        'academic_output_id' => $ao->id,
                                        'faculty_id' => $facultyId,
                                        'name' => $faculty?->full_name,
                                        'role' => $cm['role'],
                                        'appointed_date' => $cm['appointed_date'] ?? null,
                                        'term_start_id' => $cm['term_start_id'] ?? null,
                                        'term_end_id' => $cm['term_end_id'] ?? null,
                                    ]);
                                }
                            }
                        }

                        // Sync co-authors and primary authors via pivot table
                        $coAuthorIds = $data['co_author_ids'] ?? [];
                        $primaryAuthorIds = $data['primary_author_ids'] ?? [];
                        
                        $pivotData = [$student->id => ['role' => 'primary_author']];
                        foreach ($primaryAuthorIds as $pId) {
                            $pivotData[(int) $pId] = ['role' => 'primary_author'];
                        }
                        foreach ($coAuthorIds as $coAuthorId) {
                            $pivotData[(int) $coAuthorId] = ['role' => 'co_author'];
                        }
                        $ao->students()->sync($pivotData);
                    }

                    $this->editingAoId = null;

                    Notification::make()
                        ->title($msg)
                        ->success()
                        ->duration(3000)
                        ->send();
                }),

            // ── Delete Student Record (inside Manage Student dropdown) ──
            \Filament\Actions\Action::make('deleteStudentHistory')
                ->label('Delete Student')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Student Record')
                ->modalDescription('Are you sure you want to delete this student? All their enrollments, milestones, and history will be permanently deleted. This action cannot be undone.')
                ->action(function (): void {
                    $student = $this->getStudentRecord();
                    if ($student) {
                        $student->forceDelete();
                        Notification::make()->title('Student Record Deleted')->success()->duration(3000)->send();
                        $this->backToList();
                    }
                }),
            ])->label('Manage Student')->button()->icon('heroicon-m-pencil-square')
                ->visible(fn () => !empty($this->studentInfo) && !auth()->user()->hasRole('viewer')),

            // ── Edit Graduate Info ──
            // Standalone action (outside dropdown) — mounted via blade Edit button on graduation card
            // visible() ensures it doesn't render as a button; blade sets editingGraduateId before mounting
            \Filament\Actions\Action::make('editGraduateInfo')
                ->label('Edit Graduation Info')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->extraAttributes(['class' => 'hidden'])
                ->visible(fn () => !empty($this->editingGraduateId) && !auth()->user()->hasRole('viewer'))
                ->modalHeading('Edit Graduation Information')
                ->modalWidth('4xl')
                ->fillForm(function (): array {
                    $graduate = $this->editingGraduateId
                        ? Graduate::find($this->editingGraduateId)
                        : Graduate::where('student_number', $this->studentNumber)->first();
                    if (!$graduate) return [];

                    // Run the same resolution logic as the display builder
                    $allPrograms = \App\Models\Program::with('majors')->get();
                    $degreeAliases = ['dmg' => 'mdmg'];
                    $degreeLookup = $degreeAliases[mb_strtolower($graduate->degree ?? '')] ?? $graduate->degree;

                    $matchedProgram = $graduate->program;
                    if (!$matchedProgram && $degreeLookup) {
                        $matchedProgram = $allPrograms->first(fn($p) => mb_strtolower($p->code) === mb_strtolower($degreeLookup));
                    }

                    // Data is now properly resolved in the DB — just read directly
                    $resolvedProgramName = $graduate->program_name;
                    $resolvedMajorField = $graduate->major;
                    $resolvedDegree = $graduate->degree;

                    $student = Student::with(['committeeMembers.faculty'])->where('student_number', $this->studentNumber)->first();
                    $chair = '';
                    $coChair = '';
                    $members = [];

                    if ($student) {
                        foreach ($student->committeeMembers ?? [] as $cm) {
                            $name = $cm->faculty?->full_name ?? '';
                            if (!$name) continue;
                            $role = strtolower($cm->role);
                            
                            if ($role === 'adviser' && !$chair) {
                                $chair = $name;
                            } elseif (str_contains($role, 'chair') && !str_contains($role, 'co-chair') && !$chair) {
                                $chair = $name;
                            } elseif (str_contains($role, 'co-chair')) {
                                $coChair = $name;
                            } else {
                                $members[] = $name;
                            }
                        }
                    }

                    // Load committee data from normalized table, fallback to legacy flat columns
                    $normalizedMembers = $graduate->committeeMembers;
                    $committeeData = [];

                    if ($normalizedMembers->count() > 0) {
                        // Use normalized table (preferred)
                        foreach ($normalizedMembers as $gcm) {
                            if ($gcm->faculty_id || !empty($gcm->name)) {
                                $committeeData[] = [
                                    'role' => $gcm->role,
                                    'faculty_id' => $gcm->faculty_id,
                                    'appointed_date' => $gcm->appointed_date?->format('Y-m-d'),
                                    'term_start' => $gcm->term_start_id,
                                    'term_end' => $gcm->term_end_id,
                                ];
                            }
                        }
                    } else {
                        // Fallback to legacy flat columns + student committee members
                        $legacyMembers = [
                            ['role' => 'Chair', 'name' => $graduate->chair ?: $chair],
                            ['role' => 'Co-Chair', 'name' => $graduate->co_chair ?: $coChair],
                            ['role' => 'Member', 'name' => $graduate->member1 ?: ($members[0] ?? '')],
                            ['role' => 'Member', 'name' => $graduate->member2 ?: ($members[1] ?? '')],
                            ['role' => 'Member', 'name' => $graduate->member3 ?: ($members[2] ?? '')],
                            ['role' => 'Member', 'name' => $graduate->member4 ?: ($members[3] ?? '')],
                            ['role' => 'Member', 'name' => $graduate->member5 ?: ($members[4] ?? '')],
                        ];
                        foreach ($legacyMembers as $lm) {
                            $name = trim($lm['name'] ?? '');
                            if (!empty($name) && !in_array(mb_strtolower($name), ['n/a', 'na', 'none', '-', ''])) {
                                // Try to find faculty by name
                                $faculty = \App\Models\Faculty::where(\DB::raw("CONCAT(first_name, ' ', COALESCE(CONCAT(LEFT(middle_name, 1), '. '), ''), last_name)"), 'LIKE', "%{$name}%")
                                    ->orWhere(\DB::raw("CONCAT(last_name, ', ', first_name)"), 'LIKE', "%{$name}%")
                                    ->first();
                                $committeeData[] = [
                                    'role' => $lm['role'],
                                    'faculty_id' => $faculty?->id,
                                    'appointed_date' => null,
                                ];
                            }
                        }
                    }

                    return [
                        'semester_graduated' => $graduate->semester_graduated,
                        'program_id' => $graduate->program_id,
                        'program_search' => $graduate->program_id,
                        'degree' => $resolvedDegree,
                        'program_name' => $resolvedProgramName,
                        'major' => $resolvedMajorField,
                        'country_of_origin' => $graduate->country_of_origin ? ucwords(strtolower($graduate->country_of_origin)) : ($student?->country_of_origin ? ucwords(strtolower($student->country_of_origin)) : ''),
                        'committee_data' => $committeeData,
                    ];
                })
                ->form([
                    \Filament\Schemas\Components\Section::make('Graduation Details')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Select::make('semester_graduated')
                                ->label('Semester Graduated')
                                ->options(function () {
                                    return Semester::with('academicYear')
                                        ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                                        ->get()
                                        ->mapWithKeys(fn ($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
                                        ->toArray();
                                })
                                ->searchable()
                                ->placeholder('Select semester...'),
                            Forms\Components\Select::make('program_search')
                                ->label('Select from System Programs')
                                ->options(\App\Models\Program::all()->mapWithKeys(fn($p) => [$p->id => "{$p->code} — {$p->name}"]))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $program = \App\Models\Program::find($state);
                                    if ($program) {
                                        $set('degree', $program->code);
                                        $set('program_name', $program->name);
                                        $set('program_id', $program->id);
                                        $set('major', null);
                                    }
                                })
                                ->columnSpanFull()
                                ->helperText('Select a program to automatically fill the Degree and Program below, or leave blank to enter manually.')
                                ->dehydrated(false),
                            Forms\Components\Hidden::make('program_id'),
                            Forms\Components\Select::make('degree')
                                ->label('Degree')
                                ->options(\App\Models\Program::pluck('code', 'code')->toArray())
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $program = \App\Models\Program::where('code', $state)->first();
                                        if ($program) {
                                            $set('program_name', $program->name);
                                            $set('program_id', $program->id);
                                            $set('major', null);
                                        }
                                    }
                                })
                                ->placeholder('e.g. MDMG, PhD-DVST'),
                            Forms\Components\Select::make('program_name')
                                ->label('Program')
                                ->options(\App\Models\Program::all()->mapWithKeys(fn($p) => [$p->name => $p->name]))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $program = \App\Models\Program::where('name', $state)->first();
                                        if ($program) {
                                            $set('degree', $program->code);
                                            $set('program_id', $program->id);
                                            $set('major', null);
                                        }
                                    }
                                })
                                ->placeholder('Select program...'),
                            Forms\Components\Select::make('major')
                                ->label('Specialization')
                                ->options(function (callable $get) {
                                    $programId = $get('program_id');
                                    if ($programId) {
                                        $majors = \App\Models\ProgramMajor::where('program_id', $programId)
                                            ->orderBy('name')->pluck('name', 'name')->toArray();
                                        if (!empty($majors)) {
                                            return $majors;
                                        }
                                    }
                                    return [];
                                })
                                ->searchable()
                                ->placeholder('Select specialization (if applicable)...')
                                ->nullable(),
                            Forms\Components\Select::make('country_of_origin')
                                ->label('Country of Origin')
                                ->options(\App\Models\Student::getCountries())
                                ->searchable(),
                        ])->columns(2),
                    \Filament\Schemas\Components\Section::make('Advisory Committee')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            Forms\Components\Repeater::make('committee_data')
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
                                ->reorderable(false),
                        ]),
                ])
                ->action(function (array $data): void {
                    $graduate = $this->editingGraduateId
                        ? Graduate::find($this->editingGraduateId)
                        : Graduate::where('student_number', $this->studentNumber)->first();
                    if ($graduate) {
                        $committeeData = $data['committee_data'] ?? [];

                        // Sync to normalized graduate_committee_members table
                        $graduate->committeeMembers()->delete();
                        $data['chair'] = '';
                        $data['co_chair'] = '';
                        $data['member1'] = '';
                        $data['member2'] = '';
                        $data['member3'] = '';
                        $data['member4'] = '';
                        $data['member5'] = '';
                        $mCount = 1;
                        foreach($committeeData as $member) {
                            $facultyId = $member['faculty_id'] ?? null;
                            $faculty = $facultyId ? \App\Models\Faculty::find($facultyId) : null;
                            $memberName = $faculty ? $faculty->full_name : '';
                            $role = $member['role'] ?? 'Member';

                            // Create normalized committee member row
                            if ($facultyId) {
                                \App\Models\GraduateCommitteeMember::create([
                                    'graduate_id' => $graduate->id,
                                    'faculty_id' => $facultyId,
                                    'name' => $memberName,
                                    'role' => $role,
                                    'match_type' => 'manual',
                                    'appointed_date' => $member['appointed_date'] ?? null,
                                    'term_start_id' => $member['term_start'] ?? null,
                                    'term_end_id' => $member['term_end'] ?? null,
                                ]);
                            }

                            // Also update legacy flat columns for GS data fidelity
                            $roleLower = strtolower($role);
                            if (str_contains($roleLower, 'chair') && !str_contains($roleLower, 'co-chair') && !$data['chair']) {
                                $data['chair'] = $memberName;
                            } elseif (str_contains($roleLower, 'co-chair') && !$data['co_chair']) {
                                $data['co_chair'] = $memberName;
                            } else {
                                if ($mCount <= 5) {
                                    $data["member{$mCount}"] = $memberName;
                                    $mCount++;
                                }
                            }
                        }
                        unset($data['committee_data']);
                        $graduate->update($data);
                    }

                    $this->editingGraduateId = null;
                    $student = $this->getStudentRecord();
                    if ($student) {
                        $semId = null;
                        if (!empty($data['semester_graduated'])) {
                            $semId = \App\Models\Semester::where('term_code', $data['semester_graduated'])->value('id');
                        }
                        $student->update(['graduation_semester_id' => $semId]);
                    }

                    $this->search();
                    Notification::make()->title('Graduation Info Updated')->success()->duration(3000)->send();
                }),

            // ── Delete Graduate Info ──
            // Standalone action — mounted via blade Remove button on graduation card
            \Filament\Actions\Action::make('deleteGraduateInfo')
                ->label('Remove Graduation Info')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->extraAttributes(['class' => 'hidden'])
                ->visible(fn () => !empty($this->editingGraduateId) && !auth()->user()->hasRole('viewer'))
                ->modalHeading('Remove Graduation Information')
                ->modalDescription('This will permanently remove the graduation record linked to this student. The student will appear as a non-graduate. This action cannot be undone.')
                ->requiresConfirmation()
                ->action(function (): void {
                    if ($this->editingGraduateId) {
                        Graduate::where('id', $this->editingGraduateId)->delete();
                    } else {
                        Graduate::where('student_number', $this->studentNumber)->delete();
                    }
                    $this->editingGraduateId = null;
                    $student = $this->getStudentRecord();
                    if ($student) {
                        $student->update(['student_status' => 'active', 'graduation_semester_id' => null]);
                    }
                    $this->search();
                    Notification::make()->title('Graduation Info Removed')->body('The student can now be re-matched from the Graduates page.')->success()->duration(4000)->send();
                }),
        ];
    }

}
