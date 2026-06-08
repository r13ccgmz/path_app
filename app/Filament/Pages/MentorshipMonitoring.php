<?php

namespace App\Filament\Pages;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use App\Enums\AcademicRank;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\StudentCommitteeMember;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;

class MentorshipMonitoring extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|\UnitEnum|null $navigationGroup = 'Faculty Management';
    protected static ?string $navigationLabel = 'Mentorship Monitoring';
    protected static ?string $title = 'Mentorship Monitoring';
    protected static ?int $navigationSort = 2;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    
    protected string $view = 'filament.pages.mentorship-monitoring';

    protected function getHeaderActions(): array
    {
        return [

            Action::make('assign_adviser')
                ->label('Assign Adviser')
                ->icon('heroicon-o-link')
                ->color('success')
                ->visible(fn () => !auth()->user()->hasRole('viewer'))
                ->form([
                    Forms\Components\Select::make('student_id')
                        ->label('Student')
                        ->getSearchResultsUsing(function (string $search) {
                            return Student::whereNull('deleted_at')
                                ->where(function ($q) use ($search) {
                                    $q->where('full_name', 'like', "%{$search}%")
                                      ->orWhere('student_number', 'like', "%{$search}%")
                                      ->orWhere('surname', 'like', "%{$search}%")
                                      ->orWhere('given_name', 'like', "%{$search}%");
                                })
                                ->orderBy('surname')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->full_name} ({$s->formatted_student_number})" . ($s->program ? " — {$s->program->code}" : ''),
                                ])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $s = Student::find($value);
                            return $s ? "{$s->full_name} ({$s->formatted_student_number})" . ($s->program ? " — {$s->program->code}" : '') : $value;
                        })
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('faculty_id')
                        ->label('Faculty (Adviser)')
                        ->options(function () {
                            return Faculty::where('is_external', false)
                                ->where('faculty_status', 'active')
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn ($f) => [
                                    $f->id => "{$f->full_name}" . ($f->designation ? " ({$f->designation})" : ''),
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->required(),
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
                        ->default('Adviser')
                        ->required(),
                    Forms\Components\DatePicker::make('appointed_date')
                        ->label('Appointed Date'),
                    Forms\Components\Select::make('term_start_id')
                        ->label('Term Start')
                        ->options(fn () => \App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                            ->toArray())
                        ->searchable(),
                    Forms\Components\Select::make('term_end_id')
                        ->label('Term End')
                        ->options(fn () => \App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                            ->toArray())
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $role = $data['role'] ?? 'Adviser';

                    if ($role === 'Adviser') {
                        $conflict = $this->checkAdviserConflict($data['student_id']);
                        if ($conflict) {
                            $student = Student::find($data['student_id']);
                            $this->pendingAssignment = array_merge($data, ['_action' => 'create']);
                            $this->conflictingAdviserId = $conflict->id;
                            $this->showConflictNotification($conflict->faculty->full_name, $student->full_name);
                            return;
                        }
                    }

                    // Check for existing assignment
                    $exists = StudentCommitteeMember::where('student_id', $data['student_id'])
                        ->where('faculty_id', $data['faculty_id'])
                        ->where('role', $role)
                        ->exists();

                    if ($exists) {
                        \Filament\Notifications\Notification::make()
                            ->title('Assignment already exists')
                            ->body('This faculty member is already assigned to this student with this role.')
                            ->warning()
                            ->send();
                        return;
                    }

                    StudentCommitteeMember::create([
                        'student_id' => $data['student_id'],
                        'faculty_id' => $data['faculty_id'],
                        'role' => $role,
                        'appointed_date' => $data['appointed_date'] ?? null,
                        'term_start_id' => $data['term_start_id'] ?? null,
                        'term_end_id' => $data['term_end_id'] ?? null,
                    ]);

                    $student = Student::find($data['student_id']);
                    $faculty = Faculty::find($data['faculty_id']);

                    \Filament\Notifications\Notification::make()
                        ->title('Adviser Assigned')
                        ->body("{$faculty->full_name} assigned as {$role} to {$student->full_name}")
                        ->success()
                        ->send();

                    $this->redirect(request()->header('Referer') ?: static::getUrl(), navigate: false);
                }),
        ];
    }

    /**
     * Compute the active semester IDs from the table filters.
     */
    public function getActiveSemesterIds(): array
    {
        $semesterIds = $this->getTableFilterState('semester_filter')['values'] ?? [];
        $currentOnly = ($this->getTableFilterState('current_term')['isActive'] ?? false);

        if ($currentOnly) {
            $currentSem = \App\Models\Semester::where('is_current', true)->first();
            if ($currentSem) {
                $semesterIds = [$currentSem->id];
            }
        }

        return array_map('intval', $semesterIds);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\MentorshipStatsWidget::class,
            \App\Filament\Widgets\FacultyAdvisoryEligibilityWidget::class,
            \App\Filament\Widgets\AdviseeDistributionChart::class,
        ];
    }

    protected function getHeaderWidgetsData(): array
    {
        return [
            'semesterIds' => $this->getActiveSemesterIds(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Faculty::query()->where('is_external', false))
            ->modifyQueryUsing(function (Builder $query) {
                // Get the selected semester IDs from the filter
                $semesterIds = $this->getTableFilterState('semester_filter')['values'] ?? [];
                $currentOnly = ($this->getTableFilterState('current_term')['isActive'] ?? false);

                // If "Current Term" toggle is on, find the current semester
                if ($currentOnly) {
                    $currentSem = \App\Models\Semester::where('is_current', true)->first();
                    if ($currentSem) {
                        $semesterIds = [$currentSem->id];
                    }
                }

                // Build term condition for each committee table
                // Logic: assignment is active during a semester if:
                //   term_start_id <= semester.id AND (term_end_id >= semester.id OR term_end_id IS NULL)
                // When no semester filter is selected, no term condition is applied
                $scmTermCond = '';
                $gcmTermCond = '';
                $aocTermCond = '';

                if (!empty($semesterIds)) {
                    $ids = implode(',', array_map('intval', $semesterIds));
                    $scmTermCond = " AND (student_committee_members.term_start_id IN ({$ids}) OR student_committee_members.term_end_id IN ({$ids}))";
                    $gcmTermCond = " AND (graduate_committee_members.term_start_id IN ({$ids}) OR graduate_committee_members.term_end_id IN ({$ids}))";
                    $aocTermCond = " AND (academic_output_committee.term_start_id IN ({$ids}) OR academic_output_committee.term_end_id IN ({$ids}))";
                }

                // ── Aggregated counts from ALL 3 committee sources ──
                $query->addSelect([
                    'faculty.*',
                    // Student committee: Adviser/Co-Adviser roles
                    DB::raw("(SELECT COUNT(*) FROM student_committee_members WHERE student_committee_members.faculty_id = faculty.id AND student_committee_members.role IN ('Adviser', 'Co-Adviser'){$scmTermCond}) as student_adviser_count"),
                    // Student committee: Chair/Co-Chair
                    DB::raw("(SELECT COUNT(*) FROM student_committee_members WHERE student_committee_members.faculty_id = faculty.id AND student_committee_members.role IN ('Chair', 'Co-Chair'){$scmTermCond}) as student_chair_count"),
                    // Student committee: all other roles
                    DB::raw("(SELECT COUNT(*) FROM student_committee_members WHERE student_committee_members.faculty_id = faculty.id AND student_committee_members.role NOT IN ('Adviser', 'Co-Adviser', 'Chair', 'Co-Chair'){$scmTermCond}) as student_member_count"),

                    // Graduate committee: Chair/Co-Chair
                    DB::raw("(SELECT COUNT(*) FROM graduate_committee_members WHERE graduate_committee_members.faculty_id = faculty.id AND graduate_committee_members.role IN ('Chair', 'Co-Chair'){$gcmTermCond}) as grad_chair_count"),
                    // Graduate committee: Adviser
                    DB::raw("(SELECT COUNT(*) FROM graduate_committee_members WHERE graduate_committee_members.faculty_id = faculty.id AND graduate_committee_members.role = 'Adviser'{$gcmTermCond}) as grad_adviser_count"),
                    // Graduate committee: all members
                    DB::raw("(SELECT COUNT(*) FROM graduate_committee_members WHERE graduate_committee_members.faculty_id = faculty.id AND graduate_committee_members.role NOT IN ('Chair', 'Co-Chair', 'Adviser'){$gcmTermCond}) as grad_member_count"),

                    // Academic output committee: Adviser/Co-Adviser
                    DB::raw("(SELECT COUNT(*) FROM academic_output_committee WHERE academic_output_committee.faculty_id = faculty.id AND academic_output_committee.role IN ('adviser', 'co-adviser', 'Adviser', 'Co-Adviser'){$aocTermCond}) as ao_adviser_count"),
                    // Academic output committee: Chair/Co-Chair
                    DB::raw("(SELECT COUNT(*) FROM academic_output_committee WHERE academic_output_committee.faculty_id = faculty.id AND academic_output_committee.role IN ('chair', 'co-chair', 'Chair', 'Co-Chair'){$aocTermCond}) as ao_chair_count"),
                    // Academic output committee: all other
                    DB::raw("(SELECT COUNT(*) FROM academic_output_committee WHERE academic_output_committee.faculty_id = faculty.id AND academic_output_committee.role NOT IN ('adviser', 'co-adviser', 'Adviser', 'Co-Adviser', 'chair', 'co-chair', 'Chair', 'Co-Chair'){$aocTermCond}) as ao_member_count"),

                    // PhD advisee count (Adviser/Co-Adviser of doctorate students)
                    DB::raw("(SELECT COUNT(*) FROM student_committee_members
                        INNER JOIN students ON students.id = student_committee_members.student_id
                        INNER JOIN programs ON programs.id = students.program_id
                        WHERE student_committee_members.faculty_id = faculty.id
                        AND student_committee_members.role IN ('Adviser', 'Co-Adviser')
                        AND programs.degree_level = 'doctorate'
                        AND students.deleted_at IS NULL{$scmTermCond}) as phd_advisee_count"),
                    // MS advisee count (Adviser/Co-Adviser of master's students)
                    DB::raw("(SELECT COUNT(*) FROM student_committee_members
                        INNER JOIN students ON students.id = student_committee_members.student_id
                        INNER JOIN programs ON programs.id = students.program_id
                        WHERE student_committee_members.faculty_id = faculty.id
                        AND student_committee_members.role IN ('Adviser', 'Co-Adviser')
                        AND (programs.degree_level = 'master' OR programs.degree_level = 'master_of_science')
                        AND students.deleted_at IS NULL{$scmTermCond}) as ms_advisee_count"),

                    // Grand totals
                    DB::raw("(
                        (SELECT COUNT(*) FROM student_committee_members WHERE student_committee_members.faculty_id = faculty.id{$scmTermCond}) +
                        (SELECT COUNT(*) FROM graduate_committee_members WHERE graduate_committee_members.faculty_id = faculty.id{$gcmTermCond}) +
                        (SELECT COUNT(*) FROM academic_output_committee WHERE academic_output_committee.faculty_id = faculty.id{$aocTermCond})
                    ) as total_assignments"),
                ]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Faculty Name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(['last_name']),
                Tables\Columns\TextColumn::make('designation')
                    ->label('Academic Rank')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('highest_degree')
                    ->label('Degree')
                    ->getStateUsing(fn ($record) => $record->highest_degree_label)
                    ->badge()
                    ->color(fn ($record) => match ($record->highest_degree) {
                        'doctorate' => 'danger',
                        'masters' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Faculty\'s highest academic degree'),

                // ── Student Advisory (from student_committee_members) ──
                Tables\Columns\TextColumn::make('student_adviser_count')
                    ->label('Adviser')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable()
                    ->tooltip('Student Advisory: Adviser / Co-Adviser roles'),
                Tables\Columns\TextColumn::make('student_chair_count')
                    ->label('Chair')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable()
                    ->tooltip('Student Advisory: Chair / Co-Chair roles'),
                Tables\Columns\TextColumn::make('student_member_count')
                    ->label('Member/Other')
                    ->alignCenter()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Student Advisory: Member, Cognate, Minor, Major, etc.'),

                // ── Graduate Committee (from graduate_committee_members) ──
                Tables\Columns\TextColumn::make('grad_chair_count')
                    ->label('Grad Chair')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable()
                    ->tooltip('Graduate Committee: Chair / Co-Chair'),
                Tables\Columns\TextColumn::make('grad_adviser_count')
                    ->label('Grad Adviser')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Graduate Committee: Adviser'),
                Tables\Columns\TextColumn::make('grad_member_count')
                    ->label('Grad Member')
                    ->alignCenter()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->tooltip('Graduate Committee: Members'),

                // ── Academic Output (from academic_output_committee) ──
                Tables\Columns\TextColumn::make('ao_adviser_count')
                    ->label('AO Adviser')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable()
                    ->tooltip('Academic Output: Adviser / Co-Adviser'),
                Tables\Columns\TextColumn::make('ao_member_count')
                    ->label('AO Panel')
                    ->alignCenter()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Academic Output: Chair, Member, etc.'),

                // ── Advisee Breakdown ──
                Tables\Columns\TextColumn::make('phd_advisee_count')
                    ->label('PhD')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->sortable()
                    ->toggleable()
                    ->tooltip('PhD students advised (Adviser/Co-Adviser role)'),
                Tables\Columns\TextColumn::make('ms_advisee_count')
                    ->label('MS')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Master\'s students advised (Adviser/Co-Adviser role)'),

                // ── Total ──
                Tables\Columns\TextColumn::make('total_assignments')
                    ->label('Total')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 20 => 'danger',
                        $state >= 10 => 'warning',
                        $state > 0 => 'primary',
                        default => 'gray',
                    })
                    ->sortable()
                    ->tooltip('Total assignments across all sources'),
            ])
            ->defaultSort('last_name', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('active_only')
                    ->label('Active Faculty Only')
                    ->placeholder('All Faculty')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('faculty_status', 'active'),
                        false: fn (Builder $query) => $query->where('faculty_status', '!=', 'active'),
                        blank: fn (Builder $query) => $query,
                    ),
                Tables\Filters\TernaryFilter::make('has_assignments')
                    ->label('Has Assignments')
                    ->placeholder('All Faculty')
                    ->trueLabel('With Assignments')
                    ->falseLabel('Without Assignments')
                    ->queries(
                        true: fn (Builder $query) => $query->whereRaw('(
                            (SELECT COUNT(*) FROM student_committee_members WHERE student_committee_members.faculty_id = faculty.id) +
                            (SELECT COUNT(*) FROM graduate_committee_members WHERE graduate_committee_members.faculty_id = faculty.id) +
                            (SELECT COUNT(*) FROM academic_output_committee WHERE academic_output_committee.faculty_id = faculty.id)
                        ) > 0'),
                        false: fn (Builder $query) => $query->whereRaw('(
                            (SELECT COUNT(*) FROM student_committee_members WHERE student_committee_members.faculty_id = faculty.id) +
                            (SELECT COUNT(*) FROM graduate_committee_members WHERE graduate_committee_members.faculty_id = faculty.id) +
                            (SELECT COUNT(*) FROM academic_output_committee WHERE academic_output_committee.faculty_id = faculty.id)
                        ) = 0'),
                        blank: fn (Builder $query) => $query,
                    ),
                Tables\Filters\Filter::make('current_term')
                    ->label('Current Term Only')
                    ->toggle()
                    ->query(fn (Builder $query) => $query),
                Tables\Filters\SelectFilter::make('semester_filter')
                    ->label('Semester / Term')
                    ->multiple()
                    ->options(function () {
                        return \App\Models\Semester::with('academicYear')
                            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                            ->get()
                            ->mapWithKeys(fn ($s) => [
                                $s->id => ($s->is_current ? '● ' : '') . "[{$s->term_code}] {$s->label}",
                            ])
                            ->toArray();
                    })
                    ->query(fn (Builder $query) => $query),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->visible(fn () => !auth()->user()->hasRole('viewer'))
                    ->form([
                        Forms\Components\TextInput::make('first_name')->required(),
                        Forms\Components\TextInput::make('last_name')->required(),
                        Forms\Components\TextInput::make('middle_name')
                            ->label('Middle Name'),
                        Forms\Components\TextInput::make('suffix')->label('Suffix (e.g., Jr., III)'),
                        Forms\Components\Select::make('designation')
                            ->label('Academic Rank')
                            ->options(AcademicRank::options())
                            ->searchable()
                            ->placeholder('Select academic rank...'),
                        Forms\Components\Select::make('highest_degree')
                            ->label('Highest Degree')
                            ->options([
                                'high-school' => 'High School',
                                'vocational' => 'Vocational',
                                'bachelors' => "Bachelor's",
                                'masters' => "Master's",
                                'doctorate' => 'Doctorate',
                                'n/a' => 'N/A',
                            ])
                            ->searchable(),
                        Forms\Components\Select::make('faculty_status')
                            ->label('Faculty Status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->required(),
                    ])
                    ->after(function () {
                        $this->redirect(request()->header('Referer') ?: static::getUrl(), navigate: false);
                    }),
                \Filament\Actions\Action::make('manage_advisees')
                    ->label('Manage')
                    ->icon('heroicon-m-user-plus')
                    ->color('success')
                    ->visible(fn () => !auth()->user()->hasRole('viewer'))
                    ->modalHeading(fn (Faculty $record) => "Manage Advisees: {$record->full_name}")
                    ->modalWidth('3xl')
                    ->modalSubmitActionLabel('Assign')
                    ->form(function (Faculty $record): array {
                        return [
                            \Filament\Schemas\Components\View::make('filament.components.faculty-current-advisees')
                                ->viewData(['faculty' => $record]),
                            Forms\Components\Select::make('student_id')
                                ->label('Add Student')
                                ->getSearchResultsUsing(function (string $search) use ($record) {
                                    $existingStudentIds = StudentCommitteeMember::where('faculty_id', $record->id)
                                        ->where('role', 'Adviser')
                                        ->pluck('student_id')
                                        ->toArray();

                                    return Student::whereNull('deleted_at')
                                        ->whereNotIn('id', $existingStudentIds)
                                        ->where(function ($q) use ($search) {
                                            $q->where('full_name', 'like', "%{$search}%")
                                              ->orWhere('student_number', 'like', "%{$search}%")
                                              ->orWhere('surname', 'like', "%{$search}%")
                                              ->orWhere('given_name', 'like', "%{$search}%");
                                        })
                                        ->orderBy('surname')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($s) => [
                                            $s->id => "{$s->full_name} ({$s->formatted_student_number})" . ($s->program ? " — {$s->program->code}" : ''),
                                        ])
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    $s = Student::find($value);
                                    return $s ? "{$s->full_name} ({$s->formatted_student_number})" . ($s->program ? " — {$s->program->code}" : '') : $value;
                                })
                                ->searchable()
                                ->required()
                                ->helperText('Search by name or student number to find students.'),
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
                                ->default('Adviser'),
                            Forms\Components\DatePicker::make('appointed_date')
                                ->label('Appointed Date'),
                            Forms\Components\Select::make('term_start_id')
                                ->label('Term Start')
                                ->options(fn () => \App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                                    ->toArray())
                                ->searchable(),
                            Forms\Components\Select::make('term_end_id')
                                ->label('Term End')
                                ->options(fn () => \App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                                    ->toArray())
                                ->searchable(),
                        ];
                    })
                    ->action(function (Faculty $record, array $data): void {

                        $role = $data['role'] ?? 'Adviser';

                        if ($role === 'Adviser') {
                            $conflict = $this->checkAdviserConflict($data['student_id']);
                            if ($conflict) {
                                $student = Student::find($data['student_id']);
                                $this->pendingAssignment = array_merge($data, [
                                    'faculty_id' => $record->id,
                                    '_action' => 'create'
                                ]);
                                $this->conflictingAdviserId = $conflict->id;
                                $this->showConflictNotification($conflict->faculty->full_name, $student->full_name);
                                return;
                            }
                        }

                        $exists = StudentCommitteeMember::where('student_id', $data['student_id'])
                            ->where('faculty_id', $record->id)
                            ->where('role', $role)
                            ->exists();

                        if ($exists) {
                            \Filament\Notifications\Notification::make()
                                ->title('Already assigned')
                                ->warning()
                                ->send();
                            return;
                        }

                        StudentCommitteeMember::create([
                            'student_id' => $data['student_id'],
                            'faculty_id' => $record->id,
                            'role' => $role,
                            'appointed_date' => $data['appointed_date'] ?? null,
                            'term_start_id' => $data['term_start_id'] ?? null,
                            'term_end_id' => $data['term_end_id'] ?? null,
                        ]);

                        $student = Student::find($data['student_id']);
                        \Filament\Notifications\Notification::make()
                            ->title('Adviser Assigned')
                            ->body("{$student->full_name} assigned to {$record->full_name} as {$role}")
                            ->success()
                            ->send();

                        $this->redirect(request()->header('Referer') ?: static::getUrl(), navigate: false);
                    }),
                \Filament\Actions\Action::make('view_students')
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(fn (Faculty $record) => "Mentorship Details: {$record->full_name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('4xl')
                    ->modalContent(function (Faculty $record) {
                        return view('filament.components.faculty-mentorship-details', [
                            'faculty' => $record,
                            'semesterIds' => $this->getActiveSemesterIds(),
                        ]);
                    }),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Remove a student committee member assignment (called from blade view).
     */
    public function removeAssignment(int $id): void
    {
        abort_if(auth()->user()?->hasRole('viewer'), 403, 'Unauthorized action.');

        $cm = StudentCommitteeMember::with(['student', 'faculty'])->find($id);
        if (!$cm) return;

        $name = $cm->student?->full_name ?? 'Unknown';
        $cm->delete();

        \Filament\Notifications\Notification::make()
            ->title('Assignment Removed')
            ->body("{$name} unassigned")
            ->success()
            ->duration(3000)
            ->send();

        $this->redirect(request()->header('Referer') ?: static::getUrl(), navigate: false);
    }


    public ?int $editingAssignmentId = null;
    public ?string $editingRole = '';
    public ?string $editingAppointedDate = null;
    public ?int $editingTermStartId = null;
    public ?int $editingTermEndId = null;

    public ?array $pendingAssignment = null;
    public ?int $conflictingAdviserId = null;

    protected function checkAdviserConflict(int $studentId, ?int $excludeAssignmentId = null): ?StudentCommitteeMember
    {
        $query = StudentCommitteeMember::where('student_id', $studentId)
            ->where('role', 'Adviser')
            ->whereNull('term_end_id')
            ->with('faculty');
        
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }
        
        return $query->first();
    }

    #[On('resolveConflictAsFormer')]
    public function resolveConflictAsFormer(): void
    {
        if ($this->conflictingAdviserId) {
            StudentCommitteeMember::where('id', $this->conflictingAdviserId)
                ->update(['role' => 'Former Adviser']);
        }
        $this->executePendingAssignment();
    }

    #[On('resolveConflictAsCoAdviser')]
    public function resolveConflictAsCoAdviser(): void
    {
        if ($this->conflictingAdviserId) {
            StudentCommitteeMember::where('id', $this->conflictingAdviserId)
                ->update(['role' => 'Co-Adviser']);
        }
        $this->executePendingAssignment();
    }

    #[On('resolveConflictKeepBoth')]
    public function resolveConflictKeepBoth(): void
    {
        $this->executePendingAssignment();
    }

    protected function executePendingAssignment(): void
    {
        abort_if(auth()->user()?->hasRole('viewer'), 403, 'Unauthorized action.');

        if (!$this->pendingAssignment) return;
        
        $data = $this->pendingAssignment;
        
        if (isset($data['_action']) && $data['_action'] === 'update') {
            $cm = StudentCommitteeMember::find($data['id']);
            if ($cm) {
                $cm->update([
                    'role' => $data['role'],
                    'appointed_date' => $data['appointed_date'] ?: null,
                    'term_start_id' => $data['term_start_id'] ?: null,
                    'term_end_id' => $data['term_end_id'] ?: null,
                ]);
            }
        } else {
            StudentCommitteeMember::create([
                'student_id' => $data['student_id'],
                'faculty_id' => $data['faculty_id'],
                'role' => $data['role'],
                'appointed_date' => $data['appointed_date'] ?? null,
                'term_start_id' => $data['term_start_id'] ?? null,
                'term_end_id' => $data['term_end_id'] ?? null,
            ]);
        }
        
        $this->pendingAssignment = null;
        $this->conflictingAdviserId = null;
        
        \Filament\Notifications\Notification::make()
            ->title('Assignment Saved')
            ->success()
            ->send();
            
        $this->redirect(request()->header('Referer') ?: static::getUrl(), navigate: false);
    }

    protected function showConflictNotification(string $existingAdviserName, string $studentName): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Adviser Conflict Detected')
            ->body(new \Illuminate\Support\HtmlString("<strong>{$studentName}</strong> already has an active Primary Adviser: <strong>{$existingAdviserName}</strong>.<br><br>Choose how to proceed:"))
            ->warning()
            ->persistent()
            ->actions([
                \Filament\Actions\Action::make('set_former')
                    ->label("Designate {$existingAdviserName} as Former Adviser")
                    ->color('warning')
                    ->button()
                    ->dispatch('resolveConflictAsFormer')
                    ->close(),
                \Filament\Actions\Action::make('set_co_adviser')
                    ->label("Designate {$existingAdviserName} as Co-Adviser")
                    ->color('info')
                    ->button()
                    ->dispatch('resolveConflictAsCoAdviser')
                    ->close(),
                \Filament\Actions\Action::make('keep_both')
                    ->label('Keep Both as Primary Advisers')
                    ->color('gray')
                    ->button()
                    ->dispatch('resolveConflictKeepBoth')
                    ->close(),
            ])
            ->send();
    }

    public function startEditingAssignment(int $id): void
    {
        abort_if(auth()->user()?->hasRole('viewer'), 403, 'Unauthorized action.');

        $cm = StudentCommitteeMember::find($id);
        if (!$cm) return;

        $this->editingAssignmentId = $id;
        $this->editingRole = $cm->role;
        $this->editingAppointedDate = $cm->appointed_date?->format('Y-m-d') ?? ($cm->appointed_date ? date('Y-m-d', strtotime($cm->appointed_date)) : null);
        $this->editingTermStartId = $cm->term_start_id;
        $this->editingTermEndId = $cm->term_end_id;
    }

    public function cancelEditingAssignment(): void
    {
        $this->editingAssignmentId = null;
        $this->editingRole = null;
        $this->editingAppointedDate = null;
        $this->editingTermStartId = null;
        $this->editingTermEndId = null;
    }

    public function saveAssignment(): void
    {
        if (!$this->editingAssignmentId) return;

        $cm = StudentCommitteeMember::find($this->editingAssignmentId);
        if (!$cm) {
            $this->cancelEditingAssignment();
            return;
        }

        $role = $this->editingRole;
        if ($role === 'Adviser') {
            $conflict = $this->checkAdviserConflict($cm->student_id, $cm->id);
            if ($conflict) {
                $student = Student::find($cm->student_id);
                $this->pendingAssignment = [
                    'id' => $cm->id,
                    'student_id' => $cm->student_id,
                    'faculty_id' => $cm->faculty_id,
                    'role' => $this->editingRole,
                    'appointed_date' => $this->editingAppointedDate,
                    'term_start_id' => $this->editingTermStartId,
                    'term_end_id' => $this->editingTermEndId,
                    '_action' => 'update',
                ];
                $this->conflictingAdviserId = $conflict->id;
                $this->cancelEditingAssignment();
                $this->showConflictNotification($conflict->faculty->full_name, $student->full_name);
                return;
            }
        }

        $cm->update([
            'role' => $this->editingRole,
            'appointed_date' => $this->editingAppointedDate ?: null,
            'term_start_id' => $this->editingTermStartId ?: null,
            'term_end_id' => $this->editingTermEndId ?: null,
        ]);

        $this->cancelEditingAssignment();

        \Filament\Notifications\Notification::make()
            ->title('Assignment Updated')
            ->body('Role and term details saved successfully.')
            ->success()
            ->send();

        $this->redirect(request()->header('Referer') ?: static::getUrl(), navigate: false);
    }
}
