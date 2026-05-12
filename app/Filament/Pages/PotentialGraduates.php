<?php

namespace App\Filament\Pages;

use App\Models\Enrollee;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProgram;
use App\Services\ProgramMatcher;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PotentialGraduates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\UnitEnum|null $navigationGroup = 'Student Management';
    protected static ?string $navigationLabel = 'Student Progress';
    protected static ?string $title = 'Student Progress';
    protected static ?int $navigationSort = 5;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $slug = 'student-progress';
    protected string $view = 'filament.pages.student-progress';

    /**
     * Header action: Sync student data from enrollees.
     * Auto-creates students, student_programs, and student_enrollments
     * for any enrollees that don't yet have corresponding records.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncData')
                ->label('Sync Student Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Sync Student Data')
                ->modalDescription('This will create student records, program links, and enrollment entries for any new enrollees. Existing data will not be overwritten.')
                ->action(function () {
                    $result = $this->syncAllStudentData();
                    Notification::make()
                        ->title('Sync Complete')
                        ->body("Created {$result['students_created']} students, {$result['programs_created']} programs, {$result['enrollments_created']} enrollments.")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StudentProgram::query()
                    ->with(['student.committeeMembers.faculty', 'program', 'programMajor', 'admissionSemester'])
                    ->whereHas('program', fn (Builder $q) => $q->where('total_units_required', '>', 0))
                    ->selectRaw('student_programs.*, (
                            SELECT COALESCE(SUM(se.units_earned), 0)
                            FROM student_enrollments se
                            WHERE se.student_program_id = student_programs.id
                            AND se.status = ?
                        ) AS live_units_earned', ['completed'])
            )
            ->defaultSort('live_units_earned', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('student.student_number')
                    ->label('Student #')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string =>
                        Enrollee::formatStudentNumber($state)
                    ),
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('program.code')
                    ->label('Program')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('programMajor.name')
                    ->label('Major')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('live_units_earned')
                    ->label('Units Earned')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('program.total_units_required')
                    ->label('Required')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('completion_pct')
                    ->label('Completion')
                    ->state(function (StudentProgram $record): string {
                        $required = $record->program?->total_units_required;
                        if (! $required || $required <= 0) return '—';
                        $earned = (int) ($record->live_units_earned ?? $record->total_units_earned ?? 0);
                        $pct = min(100, round(($earned / $required) * 100, 1));
                        return "{$pct}%";
                    })
                    ->badge()
                    ->color(function (StudentProgram $record): string {
                        $required = $record->program?->total_units_required;
                        if (! $required || $required <= 0) return 'gray';
                        $earned = (int) ($record->live_units_earned ?? $record->total_units_earned ?? 0);
                        $pct = ($earned / $required) * 100;
                        if ($pct >= 100) return 'success';
                        if ($pct >= 75) return 'info';
                        if ($pct >= 50) return 'warning';
                        return 'gray';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            DB::raw('live_units_earned / (SELECT total_units_required FROM programs WHERE programs.id = student_programs.program_id)'),
                            $direction
                        );
                    })
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('gwa')
                    ->label('GWA')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 3) : '—'),
                Tables\Columns\TextColumn::make('residency_enrolled')
                    ->label('Semesters')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('student_adviser_name')
                    ->label('Adviser')
                    ->getStateUsing(function ($record) {
                        $adviser = $record->student?->committeeMembers?->firstWhere('role', 'Adviser');
                        return $adviser?->faculty?->full_name ?? '-';
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'candidate' => 'Candidate',
                        'graduated' => 'Graduated',
                        'active' => 'Active',
                        default => ucfirst($state ?? ''),
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'candidate' => 'warning',
                        'graduated' => 'primary',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('program_id')
                    ->label('Program')
                    ->relationship('program', 'code')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'candidate' => 'Candidate for Graduation',
                        'graduated' => 'Graduated',
                    ]),
                Tables\Filters\Filter::make('completion_slider')
                    ->label('Completion Range')
                    ->form([
                        Forms\Components\TextInput::make('min')->hidden(),
                        Forms\Components\TextInput::make('max')->hidden(),
                        Forms\Components\ViewField::make('range')
                            ->view('filament.forms.components.range-slider'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        $min = $data['min'] ?? null;
                        $max = $data['max'] ?? null;
                        if ($min === null && $max === null) return null;
                        if ($min == 0 && $max == 100) return null;
                        return "Completion: {$min}% - {$max}%";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $min = (int) ($data['min'] ?? 0) / 100;
                        $max = (int) ($data['max'] ?? 100) / 100;
                        if ($min == 0 && $max == 1) return $query;
                        
                        return $query->whereRaw(
                            '(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) >= (SELECT total_units_required FROM programs WHERE programs.id = student_programs.program_id) * ?',
                            ['completed', $min]
                        )->whereRaw(
                            '(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) <= (SELECT total_units_required FROM programs WHERE programs.id = student_programs.program_id) * ?',
                            ['completed', $max]
                        );
                    }),
                Tables\Filters\SelectFilter::make('adviser')
                    ->label('Adviser')
                    ->options(function () {
                        return \App\Models\Faculty::orderBy('last_name')->get()->mapWithKeys(fn ($f) => [$f->id => $f->full_name])->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->whereHas('student', fn ($q) => $q->whereHas('committeeMembers', fn ($q2) => $q2->where('role', 'Adviser')->where('faculty_id', $data['value'])));
                    }),
                Tables\Filters\Filter::make('candidates')
                    ->label('Candidates for Graduation')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereRaw(
                            '(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) >= (SELECT total_units_required FROM programs WHERE programs.id = student_programs.program_id)',
                            ['completed']
                        )
                    ),
            ])
            ->recordUrl(fn (StudentProgram $record): string =>
                '/admin/list-of-students?studentNumber=' . urlencode($record->student->student_number)
            )
            ->emptyStateHeading('No student progress found')
            ->emptyStateDescription('Import enrollee data and click "Sync Student Data" to populate this table.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Sync all enrollee data into students, student_programs, and student_enrollments.
     * Only creates missing records — safe to run multiple times.
     */
    private function syncAllStudentData(): array
    {
        $matcher = ProgramMatcher::instance();
        $studentsCreated = 0;
        $programsCreated = 0;
        $enrollmentsCreated = 0;

        // Step 1: Create missing Student records from enrollees
        $existingStudentNumbers = Student::pluck('student_number')->toArray();
        $missingEnrollees = Enrollee::query()
            ->select('student_number')
            ->distinct()
            ->whereNotIn('student_number', $existingStudentNumbers)
            ->pluck('student_number');

        foreach ($missingEnrollees as $studentNumber) {
            $latest = Enrollee::where('student_number', $studentNumber)->orderByDesc('term_id')->first();
            $first = Enrollee::where('student_number', $studentNumber)->orderBy('term_id')->first();
            if (!$latest) continue;

            $admSemester = $first ? \App\Models\Semester::where('term_code', $first->term_id)->first() : null;

            Student::create([
                'student_number' => $studentNumber,
                'surname' => $latest->last_name ?? '',
                'given_name' => $latest->first_name ?? '',
                'middle_name' => $latest->middle_name ?? null,
                'full_name' => $latest->full_name ?? ($latest->last_name . ', ' . $latest->first_name),
                'email' => $latest->email ?? null,
                'program_id' => $matcher->match($latest->degree_program ?? ''),
                'admission_semester_id' => null,
                'student_status' => 'active',
            ]);
            $studentsCreated++;
        }

        // Step 2: Create missing StudentProgram records
        $students = Student::all();
        foreach ($students as $student) {
            $enrollees = Enrollee::where('student_number', $student->student_number)
                ->whereNotNull('degree_program')
                ->where('degree_program', '!=', '')
                ->orderBy('term_id')
                ->get();

            $byProgram = $enrollees->groupBy('degree_program');
            $seenProgramIds = StudentProgram::where('student_id', $student->id)
                ->pluck('program_id')->toArray();

            foreach ($byProgram as $rawDegree => $records) {
                $programId = $matcher->match($rawDegree);
                if (!$programId || in_array($programId, $seenProgramIds)) continue;
                $seenProgramIds[] = $programId;

                $termIds = $records->pluck('term_id')->unique()->sort()->values();
                $firstTerm = $termIds->first();
                $admSemester = \App\Models\Semester::where('term_code', $firstTerm)->first();

                StudentProgram::create([
                    'student_id' => $student->id,
                    'program_id' => $programId,
                    'raw_degree_name' => $rawDegree,
                    'admission_semester_id' => null,
                    'status' => 'active',
                    'residency_enrolled' => $records->filter(fn ($e) =>
                        str_contains(strtoupper($e->courses_enrolled ?? ''), 'RESID')
                    )->pluck('term_id')->unique()->count(),
                ]);
                $programsCreated++;
            }
        }

        // Step 3: Create missing StudentEnrollment records from enrollment_course_enrollee
        $studentLookup = Student::pluck('id', 'student_number')->toArray();
        $semesterLookup = DB::table('semesters')->pluck('id', 'term_code')->toArray();
        $courseLookup = \App\Models\Course::pluck('id', 'course_code')->toArray();

        // Get enrollments that don't have student_enrollment records yet
        $newEnrollments = DB::table('enrollment_course_enrollee as ece')
            ->join('enrollees as e', 'e.id', '=', 'ece.enrollee_id')
            ->join('enrollment_courses as ec', 'ec.id', '=', 'ece.enrollment_course_id')
            ->select(['e.student_number', 'e.term_id', 'ec.course_code'])
            ->whereNotNull('e.student_number')
            ->where('e.student_number', '!=', '')
            ->get();

        foreach ($newEnrollments->groupBy(fn ($r) => $r->student_number . '|' . $r->course_code . '|' . $r->term_id) as $key => $records) {
            [$sn, $cc, $term] = explode('|', $key);
            $studentId = $studentLookup[$sn] ?? null;
            $courseId = $courseLookup[$cc] ?? null;
            $semesterId = $semesterLookup[$term] ?? null;
            if (!$studentId || !$courseId || !$semesterId) continue;

            $exists = StudentEnrollment::where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->where('semester_id', $semesterId)
                ->exists();

            if (!$exists) {
                // Estimate units
                $units = DB::table('program_courses as pc')
                    ->join('courses as c', 'c.id', '=', 'pc.course_id')
                    ->where('c.course_code', $cc)
                    ->whereNotNull('pc.units')
                    ->value('pc.units') ?? 3;

                StudentEnrollment::create([
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'semester_id' => $semesterId,
                    'status' => 'completed',
                    'units_earned' => (int) $units,
                ]);
                $enrollmentsCreated++;
            }
        }

        // Step 4: Link orphaned student_enrollments to student_programs
        $studentPrograms = StudentProgram::with('program')->get();
        foreach ($studentPrograms as $sp) {
            $termCodes = Enrollee::where('student_number', function ($q) use ($sp) {
                $q->select('student_number')->from('students')->where('id', $sp->student_id);
            })->where('degree_program', $sp->raw_degree_name)->pluck('term_id')->unique()->toArray();

            $semIds = \App\Models\Semester::whereIn('term_code', $termCodes)->pluck('id')->toArray();

            if (!empty($semIds)) {
                StudentEnrollment::where('student_id', $sp->student_id)
                    ->whereIn('semester_id', $semIds)
                    ->whereNull('student_program_id')
                    ->update(['student_program_id' => $sp->id]);
            }
        }

        // Step 5: Mark status as graduated based on graduates table
        $graduates = \App\Models\Graduate::whereNotNull('student_number')->get();
        foreach ($graduates as $grad) {
            $student = Student::where('student_number', $grad->student_number)->first();
            if ($student) {
                if ($student->student_status === 'active') {
                    $student->update(['student_status' => 'graduated']);
                }

                $sps = StudentProgram::where('student_id', $student->id)->get();
                if ($sps->count() === 1) {
                    if ($sps->first()->status !== 'graduated') {
                        $sps->first()->update(['status' => 'graduated']);
                    }
                } else {
                    $degree = trim($grad->degree ?? '');
                    foreach ($sps as $sp) {
                        if ($sp->status !== 'graduated' && $degree && $sp->program && str_contains(strtoupper($sp->program->code), strtoupper($degree))) {
                            $sp->update(['status' => 'graduated']);
                        }
                    }
                }
            }
        }

        return [
            'students_created' => $studentsCreated,
            'programs_created' => $programsCreated,
            'enrollments_created' => $enrollmentsCreated,
        ];
    }
}
