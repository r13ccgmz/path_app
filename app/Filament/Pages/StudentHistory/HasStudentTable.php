<?php

namespace App\Filament\Pages\StudentHistory;

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
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * HasStudentTable trait extracted from StudentHistory.
 */
trait HasStudentTable
{

    // ══════════════════════════════════════════════════════════
    // ── Table ──
    // ══════════════════════════════════════════════════════════

    public function table(Table $table): Table
    {
        if ($this->studentInfo && !empty($this->studentNumber)) {
            // Show enrollment history for the searched student
            $query = Enrollee::where('student_number', $this->studentNumber);
        } else {
            // Show all students from the students table (default view)
            $query = Student::query()
                ->with(['committeeMembers.faculty', 'studentPrograms.program', 'admissionSemester'])
                ->addSelect([
                    'students.*',
                    'terms_enrolled' => DB::table('enrollees as e2')
                        ->selectRaw('COUNT(DISTINCT term_id)')
                        ->whereColumn('e2.student_number', 'students.student_number'),
                    'latest_enrollee_units' => DB::table('enrollees as e3')
                        ->select('e3.total_units')
                        ->whereColumn('e3.student_number', 'students.student_number')
                        ->orderByDesc('e3.term_id')
                        ->limit(1),
                    'latest_enrollee_status' => DB::table('enrollees as e4')
                        ->select('e4.enrollment_status')
                        ->whereColumn('e4.student_number', 'students.student_number')
                        ->orderByDesc('e4.term_id')
                        ->limit(1),
                ]);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('term_id')
                    ->label('Term')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $html = "<span>{$state}</span>";
                        if ($record->source === 'manual') {
                            $html .= ' <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 ml-2">Manual Entry</span>';
                        } else {
                            $html .= ' <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 ml-2">Imported</span>';
                        }
                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->visible(fn () => !empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Student #')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->color('primary')
                    ->formatStateUsing(function (string $state) {
                        $formatted = Enrollee::formatStudentNumber($state);
                        if (\Illuminate\Support\Str::startsWith($state, 'TEMP-')) {
                            return new \Illuminate\Support\HtmlString('<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300" title="Temporary ID for missing student number">No Student #</span>');
                        }
                        return $formatted;
                    })
                    ->visible(fn () => empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->sortable(['surname'])
                    ->searchable(['surname', 'given_name'])
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->icon('heroicon-o-user')
                    ->visible(fn () => empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('program_display')
                    ->label('Program')
                    ->getStateUsing(function ($record) {
                        if ($record instanceof Enrollee) {
                            return $record->degree_program;
                        }
                        // Student model — get from studentPrograms relationship
                        $programs = $record->studentPrograms;
                        if ($programs->isEmpty()) return '-';
                        return $programs->map(fn ($sp) => $sp->program?->code ?? $sp->raw_degree_name)->implode(', ');
                    })
                    ->badge()
                    ->color('gray')
                    ->wrap()
                    ->visible(fn () => empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('degree_program')
                    ->label('Program')
                    ->wrap()
                    ->visible(fn () => !empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('courses_enrolled')
                    ->label('Courses Enrolled')
                    ->wrap()
                    ->visible(fn () => !empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('terms_enrolled')
                    ->label('Terms')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->visible(fn () => empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('total_units')
                    ->label('Units')
                    ->sortable()
                    ->visible(fn () => !empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('enrollment_status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        if (!empty($record->enrollment_status) && $record->enrollment_status !== 'Auto') {
                            return $record->enrollment_status;
                        }
                        $units = (int) ($record->total_units ?? 0);
                        static $threshold = null;
                        if ($threshold === null) {
                            $threshold = (int) \App\Models\SystemSetting::get('full_time_units_threshold', 9);
                        }
                        return $units >= $threshold ? 'Full-Time' : 'Part-Time';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Full-Time' => 'success',
                        'Part-Time' => 'warning',
                        default => 'gray',
                    })
                    ->visible(fn () => !empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('admissionSemester.term_code')
                    ->label('Admission Semester')
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->description(function ($record) {
                        if (!$record->admissionSemester) return null;
                        return $record->admissionSemester->label;
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query
                            ->leftJoin('semesters as s_adm', 'students.admission_semester_id', '=', 's_adm.id')
                            ->orderByRaw("CAST(s_adm.term_code AS UNSIGNED) $direction")
                            ->select('students.*');
                    })
                    ->visible(fn () => empty($this->studentInfo)),
                Tables\Columns\TextColumn::make('student_status')
                    ->label('Student Status')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('-', ' ', $state ?? '')))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'graduated' => 'info',
                        'on-leave', 'leave-of-absence-approved' => 'warning',
                        'absent-without-official-leave', 'dismissed', 'dropped', 'withdrawn' => 'danger',
                        default => 'gray',
                    })
                    ->visible(fn () => empty($this->studentInfo))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('applicant_status')
                    ->label('Applicant Status')
                    ->formatStateUsing(fn ($state) => $state ? ucwords(str_replace(['-', '_'], ' ', $state)) : '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'admitted' => 'success',
                        'pending', 'for-evaluation' => 'warning',
                        'rejected', 'declined' => 'danger',
                        'enrolled' => 'info',
                        default => 'gray',
                    })
                    ->visible(fn () => empty($this->studentInfo))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('latest_enrollment_status')
                    ->label('Enrollment Status')
                    ->getStateUsing(function ($record) {
                        // Use the pre-loaded subquery value if available
                        $units = (int) ($record->latest_enrollee_units ?? 0);
                        $explicitStatus = $record->latest_enrollee_status ?? null;

                        if ($explicitStatus && $explicitStatus !== 'Auto') {
                            return $explicitStatus;
                        }
                        if ($units === 0 && !$explicitStatus) return null;

                        static $threshold = null;
                        if ($threshold === null) {
                            $threshold = (int) \App\Models\SystemSetting::get('full_time_units_threshold', 9);
                        }
                        return $units >= $threshold ? 'Full-Time' : 'Part-Time';
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Full-Time' => 'success',
                        'Part-Time' => 'warning',
                        default => 'gray',
                    })
                    ->visible(fn () => empty($this->studentInfo))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sex')
                    ->label('Gender')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '-')
                    ->visible(fn () => empty($this->studentInfo))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nationality')
                    ->label('Nationality')
                    ->getStateUsing(function ($record) {
                        $nat = $record->nationality;
                        if (is_array($nat)) return !empty($nat) ? implode(', ', $nat) : '-';
                        return $nat ?: '-';
                    })
                    ->wrap()
                    ->visible(fn () => empty($this->studentInfo))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('primary_adviser_name')
                    ->label('Primary Adviser')
                    ->getStateUsing(function ($record) {
                        $adviser = $record->committeeMembers->firstWhere('role', 'Adviser');
                        return $adviser?->faculty?->full_name ?? '-';
                    })
                    ->visible(fn () => empty($this->studentInfo))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->visible(fn () => empty($this->studentInfo))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort($this->studentInfo ? 'term_id' : 'surname')
            ->filters([
                Tables\Filters\SelectFilter::make('student_status')
                    ->label('Student Status')
                    ->multiple()
                    ->options([
                        'active' => 'Active',
                        'on-leave' => 'On Leave',
                        'leave-of-absence-approved' => 'Leave of Absence Approved',
                        'absent-without-official-leave' => 'Absent Without Official Leave',
                        'graduated' => 'Graduated',
                        'dismissed' => 'Dismissed',
                        'dropped' => 'Dropped',
                        'withdrawn' => 'Withdrawn',
                        'inactive' => 'Inactive',
                    ])
                    ->query(function ($query, array $data) {
                        $values = $data['values'] ?? [];
                        if (empty($values)) return $query;
                        return $query->whereIn('student_status', $values);
                    })
                    ->visible(fn () => empty($this->studentInfo)),
                Tables\Filters\SelectFilter::make('adviser')
                    ->label('Adviser')
                    ->options(function () {
                        return \App\Models\Faculty::orderBy('last_name')->get()->mapWithKeys(fn ($f) => [$f->id => $f->full_name])->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->whereHas('committeeMembers', fn ($q) => $q->where('role', 'Adviser')->where('faculty_id', $data['value']));
                    })
                    ->visible(fn () => empty($this->studentInfo)),
                Tables\Filters\SelectFilter::make('degree_program')
                    ->label('Program')
                    ->options(fn () => \App\Models\Program::orderBy('code')->pluck('code', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->query(function ($query, array $data) {
                        $values = $data['values'] ?? [];
                        if (empty($values)) return $query;
                        return $query->whereHas('studentPrograms', fn ($q) => $q->whereIn('program_id', $values));
                    })
                    ->visible(fn () => empty($this->studentInfo)),
                Tables\Filters\SelectFilter::make('admission_semester')
                    ->label('Admission Semester')
                    ->multiple()
                    ->options(function () {
                        // Get admission semesters that are actually assigned to students
                        return Semester::whereIn('id', function ($sub) {
                                $sub->select('admission_semester_id')
                                    ->from('students')
                                    ->whereNotNull('admission_semester_id')
                                    ->distinct();
                            })
                            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                            ->toArray();
                    })
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        $values = $data['values'] ?? [];
                        if (empty($values)) return $query;

                        return $query->whereIn('admission_semester_id', $values);
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn () => empty($this->studentInfo)),
            ])
            ->recordUrl(function ($record): ?string {
                if (!empty($this->studentInfo)) return null;
                return '/admin/list-of-students?studentNumber=' . urlencode($record->student_number);
            })
            ->emptyStateHeading(fn () => empty($this->studentInfo) ? 'No students found' : 'No enrollment records')
            ->emptyStateDescription(fn () => empty($this->studentInfo) ? 'Try adjusting your search or filters.' : 'This student has no enrollment history yet.')
            ->emptyStateIcon(fn () => empty($this->studentInfo) ? 'heroicon-o-users' : 'heroicon-o-academic-cap')
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->visible(fn ($record) => !empty($this->studentInfo))
                    ->modalHeading('Edit Enrollment Record')
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\Select::make('term_id')
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
                        Forms\Components\Select::make('enrollment_status')
                            ->label('Enrollment Status')
                            ->options([
                                'Auto' => 'Auto (Calculate from Units)',
                                'Full-Time' => 'Full-Time',
                                'Part-Time' => 'Part-Time',
                            ])
                            ->required(),
                        Forms\Components\Repeater::make('course_units')
                            ->label('Courses & Units')
                            ->schema([
                                Forms\Components\Select::make('course_code')
                                    ->label('Course')
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                        if ($state) {
                                            $units = $this->estimateCourseUnits($state);
                                            $set('units', $units);
                                        }
                                    })
                                    ->getSearchResultsUsing(function (string $search) {
                                        return Course::where('course_code', 'like', "%{$search}%")
                                            ->orWhere('course_name', 'like', "%{$search}%")
                                            ->orderBy('course_code')
                                            ->limit(30)
                                            ->get()
                                            ->mapWithKeys(fn ($c) => [
                                                $c->course_code => "{$c->course_code} — {$c->course_name}"
                                            ]);
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        $c = Course::where('course_code', $value)->first();
                                        return $c ? "{$c->course_code} — {$c->course_name}" : $value;
                                    })
                                    ->createOptionUsing(function (array $data, \Filament\Schemas\Components\Utilities\Set $set) {
                                        $code = strtoupper(trim($data['course_code']));
                                        Course::firstOrCreate(
                                            ['course_code' => $code],
                                            [
                                                'course_name' => $this->generatePlaceholderName($code),
                                                'is_active' => false,
                                            ]
                                        );
                                        $set('units', $this->estimateCourseUnits($code));
                                        return $code;
                                    })
                                    ->createOptionForm([
                                        \Filament\Forms\Components\TextInput::make('course_code')
                                            ->label('New Course Code')
                                            ->required()
                                            ->placeholder('e.g. DM 201'),
                                    ])
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('units')
                                    ->label('Units')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(3)
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Course')
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->helperText('Each course and its unit value. Total is calculated automatically.'),
                    ])
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['enrollment_status'] = $data['enrollment_status'] ?? 'Auto';
                        // Parse the comma-separated courses_enrolled into individual course+units rows
                        $courseCodes = array_map('trim', explode(',', $data['courses_enrolled'] ?? ''));
                        $courseCodes = array_values(array_filter($courseCodes));

                        $student = $this->getStudentRecord();
                        $termCode = $data['term_id'] ?? null;
                        $semesterId = $termCode ? DB::table('semesters')->where('term_code', $termCode)->value('id') : null;

                        $courseUnits = [];
                        foreach ($courseCodes as $code) {
                            $units = $this->estimateCourseUnits($code); // default fallback

                            // Try to read the stored units_earned from student_enrollments
                            if ($student && $semesterId) {
                                $courseId = DB::table('courses')->where('course_code', $code)->value('id');
                                if ($courseId) {
                                    $storedUnits = StudentEnrollment::where('student_id', $student->id)
                                        ->where('semester_id', $semesterId)
                                        ->where('course_id', $courseId)
                                        ->value('units_earned');

                                    if ($storedUnits && $storedUnits > 0) {
                                        $units = (int) $storedUnits;
                                    }
                                }
                            }

                            $courseUnits[] = [
                                'course_code' => $code,
                                'units' => $units,
                            ];
                        }
                        $data['course_units'] = $courseUnits;
                        return $data;
                    })
                    ->using(function ($record, array $data) {
                        $student = $this->getStudentRecord();
                        $courseUnitsData = collect($data['course_units'] ?? [])->values()->toArray();

                        // Build a map of course_code => units from the form
                        $courseUnitsMap = [];
                        $newCourseCodes = [];
                        $calculatedUnits = 0;
                        foreach ($courseUnitsData as $item) {
                            $code = $item['course_code'] ?? null;
                            if ($code) {
                                $newCourseCodes[] = $code;
                                $units = (int) ($item['units'] ?? 0);
                                $calculatedUnits += $units;
                                $courseUnitsMap[$code] = $units;
                            }
                        }

                        $oldCourseCodes = array_map('trim', explode(',', $record->courses_enrolled ?? ''));
                        $oldCourseCodes = array_values(array_filter($oldCourseCodes));

                        // Capture old term BEFORE updating the record
                        $oldTermId = $record->term_id;

                        // Force-update the enrollee record via direct DB to avoid Eloquent interference
                        DB::table('enrollees')
                            ->where('id', $record->id)
                            ->update([
                                'courses_enrolled' => implode(', ', $newCourseCodes),
                                'term_id' => $data['term_id'],
                                'total_units' => $calculatedUnits,
                                'enrollment_status' => ($data['enrollment_status'] ?? 'Auto') !== 'Auto' ? $data['enrollment_status'] : null,
                                'updated_at' => now(),
                            ]);

                        // Refresh the record so Filament sees the new values
                        $record->refresh();

                        if (!$student) return $record;

                        $semesterId = DB::table('semesters')
                            ->where('term_code', $data['term_id'])
                            ->value('id');

                        // Resolve old semester from enrollee's previous term
                        $oldSemesterFromEnrollee = DB::table('semesters')
                            ->where('term_code', $oldTermId)
                            ->value('id');

                        // Also detect the ACTUAL semester the student_enrollments are currently on
                        // (they may be out of sync from prior failed edits)
                        $oldCourseIds = Course::whereIn('course_code', $oldCourseCodes)->pluck('id')->toArray();
                        $actualOldSemesterId = null;
                        if (!empty($oldCourseIds)) {
                            $actualOldSemesterId = StudentEnrollment::where('student_id', $student->id)
                                ->whereIn('course_id', $oldCourseIds)
                                ->value('semester_id');
                        }
                        // Use the actual semester from student_enrollments, falling back to enrollee term
                        $oldSemesterId = $actualOldSemesterId ?? $oldSemesterFromEnrollee;

                        // Determine the correct student_program_id by matching the enrollee's degree_program
                        $studentProgramId = null;
                        $enrolleeDegreeProgram = $record->degree_program ?? null;
                        if ($enrolleeDegreeProgram) {
                            $matchedProgramId = DB::table('programs')
                                ->where('name', $enrolleeDegreeProgram)
                                ->value('id');
                            if ($matchedProgramId) {
                                $studentProgramId = \App\Models\StudentProgram::where('student_id', $student->id)
                                    ->where('program_id', $matchedProgramId)
                                    ->value('id');
                            }
                        }
                        // Fallback: use existing enrollment's program or first available
                        if (!$studentProgramId) {
                            $studentProgramId = StudentEnrollment::where('student_id', $student->id)
                                ->value('student_program_id')
                                ?? \App\Models\StudentProgram::where('student_id', $student->id)->value('id');
                        }

                        // Use the old semester for finding/removing existing enrollments
                        $workingSemesterId = $oldSemesterId ?? $semesterId;

                        // Find added and removed courses
                        $addedCodes = array_diff($newCourseCodes, $oldCourseCodes);
                        $removedCodes = array_diff($oldCourseCodes, $newCourseCodes);

                        // Remove student_enrollments for removed courses
                        if (!empty($removedCodes) && $workingSemesterId) {
                            $removedCourseIds = Course::whereIn('course_code', $removedCodes)->pluck('id')->toArray();
                            if (!empty($removedCourseIds)) {
                                StudentEnrollment::where('student_id', $student->id)
                                    ->where('semester_id', $workingSemesterId)
                                    ->whereIn('course_id', $removedCourseIds)
                                    ->delete();

                                // Clean up manually-classified curriculum entries
                                $this->cleanupManualCurriculumEntries($student, $removedCourseIds);
                            }
                        }

                        // Create student_enrollments for added courses
                        if (!empty($addedCodes) && $semesterId && $studentProgramId) {
                            foreach ($addedCodes as $code) {
                                $course = Course::where('course_code', $code)->first();
                                if (!$course) continue;

                                $exists = StudentEnrollment::where('student_id', $student->id)
                                    ->where('course_id', $course->id)
                                    ->where('semester_id', $semesterId)
                                    ->exists();

                                if (!$exists) {
                                    StudentEnrollment::create([
                                        'student_id' => $student->id,
                                        'student_program_id' => $studentProgramId,
                                        'course_id' => $course->id,
                                        'semester_id' => $semesterId,
                                        'status' => 'enrolled',
                                        'units_earned' => $courseUnitsMap[$code] ?? 0,
                                        'source' => 'manual',
                                    ]);
                                }
                            }
                        }

                        // Update units_earned for ALL courses (including existing ones)
                        $unitUpdateSemester = $oldSemesterId ?? $semesterId;
                        if ($unitUpdateSemester) {
                            foreach ($courseUnitsMap as $code => $units) {
                                $courseId = DB::table('courses')->where('course_code', $code)->value('id');
                                if ($courseId) {
                                    StudentEnrollment::where('student_id', $student->id)
                                        ->where('semester_id', $unitUpdateSemester)
                                        ->where('course_id', $courseId)
                                        ->update(['units_earned' => $units]);
                                }
                            }
                        }

                        // Migrate semester for all existing enrollments if term changed
                        if ($semesterId && $oldSemesterId && $oldSemesterId !== $semesterId) {
                            $existingCourseIds = Course::whereIn('course_code', $newCourseCodes)->pluck('id')->toArray();
                            foreach ($existingCourseIds as $cid) {
                                // Only update if the target doesn't already exist
                                $exists = StudentEnrollment::where('student_id', $student->id)
                                    ->where('semester_id', $semesterId)
                                    ->where('course_id', $cid)
                                    ->exists();
                                if (!$exists) {
                                    StudentEnrollment::where('student_id', $student->id)
                                        ->where('semester_id', $oldSemesterId)
                                        ->where('course_id', $cid)
                                        ->update(['semester_id' => $semesterId]);
                                } else {
                                    // Target already exists — just delete the old one to avoid duplicates
                                    StudentEnrollment::where('student_id', $student->id)
                                        ->where('semester_id', $oldSemesterId)
                                        ->where('course_id', $cid)
                                        ->delete();
                                }
                            }
                        }

                        return $record;
                    }),
                \Filament\Actions\DeleteAction::make('deleteStudent')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn ($record) => empty($this->studentInfo))
                    ->modalHeading('Delete Student Record')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->forceDelete();
                        Notification::make()->title('Student Deleted')->success()->send();
                    }),
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !empty($this->studentInfo))
                    ->modalHeading('Delete Enrollment Record')
                    ->after(function ($record) {
                        // Clean up corresponding student_enrollments when an enrollee record is deleted
                        $student = $this->getStudentRecord();
                        if (!$student) return;

                        $termCode = $record->term_id;
                        $semesterId = DB::table('semesters')
                            ->where('term_code', $termCode)
                            ->value('id');

                        if ($semesterId) {
                            // Parse courses from the deleted enrollee record
                            $courseCodes = array_map('trim', explode(',', $record->courses_enrolled ?? ''));
                            $courseIds = Course::whereIn('course_code', $courseCodes)->pluck('id')->toArray();

                            if (!empty($courseIds)) {
                                // Detect ACTUAL semester id from student_enrollments to handle out-of-sync cases
                                $actualOldSemesterId = StudentEnrollment::where('student_id', $student->id)
                                    ->whereIn('course_id', $courseIds)
                                    ->value('semester_id');
                                $workingSemesterId = $actualOldSemesterId ?? $semesterId;

                                $deleted = StudentEnrollment::where('student_id', $student->id)
                                    ->where('semester_id', $workingSemesterId)
                                    ->whereIn('course_id', $courseIds)
                                    ->delete();

                                // Clean up manually-classified curriculum entries
                                $this->cleanupManualCurriculumEntries($student, $courseIds);

                                // Check if any student_programs are now orphaned (no enrollments left)
                                $studentPrograms = \App\Models\StudentProgram::where('student_id', $student->id)->get();
                                foreach ($studentPrograms as $sp) {
                                    $remaining = StudentEnrollment::where('student_program_id', $sp->id)->count();
                                    if ($remaining === 0) {
                                        // Clear students.program_id if it matches this orphaned program
                                        if ($student->program_id === $sp->program_id) {
                                            $student->program_id = null;
                                            $student->save();
                                        }
                                        $sp->delete();
                                    }
                                }

                                // Also clear students.program_id if NO student_programs remain at all
                                // and the deleted record's degree matched the student's assigned program
                                if (\App\Models\StudentProgram::where('student_id', $student->id)->count() === 0) {
                                    if ($student->program_id) {
                                        $matcher = \App\Services\ProgramMatcher::instance();
                                        $matchedProgramId = $matcher->match($record->degree_program ?? '');
                                        if ($matchedProgramId && $matchedProgramId === $student->program_id) {
                                            $student->program_id = null;
                                            $student->save();
                                        }
                                    }

                                    // If the student has zero enrollees left, clear program_id unconditionally
                                    $remainingEnrollees = Enrollee::where('student_number', $student->student_number)->count();
                                    if ($remainingEnrollees === 0 && $student->program_id) {
                                        $student->program_id = null;
                                        $student->save();
                                    }
                                }
                            }
                        }
                    })
            ])
            ->headerActions([
                \Filament\Actions\Action::make('addStudent')
                    ->label('Add Student')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalHeading('Add New Student')
                    ->modalDescription('Manually create a student record. This student will appear in the list immediately.')
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Create Student')
                    ->visible(fn () => empty($this->studentInfo))
                    ->form([
                        \Filament\Schemas\Components\Section::make('Personal Information')
                            ->schema([
                                Forms\Components\TextInput::make('student_number')
                                    ->label('Student Number')
                                    ->required()
                                    ->placeholder('e.g. 202012345')
                                    ->unique('students', 'student_number', modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->whereNull('deleted_at')),
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
                                Forms\Components\TextInput::make('email')
                                    ->maxLength(255),
                            ])->columns(2),
                        \Filament\Schemas\Components\Section::make('Contact Details')
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
                        \Filament\Schemas\Components\Section::make('Demographics & Address')
                            ->schema([
                                Forms\Components\Select::make('sex')
                                    ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),
                                Forms\Components\DatePicker::make('birthdate'),
                                Forms\Components\Select::make('nationality')
                                    ->label('Nationality(s)')
                                    ->options(\App\Models\Student::getNationalities())
                                    ->multiple()
                                    ->searchable()
                                    ->default(['Filipino']),
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
                        \Filament\Schemas\Components\Section::make('Status, Admission & Committee')
                            ->description('Academic status, admission details, and committee assignments.')
                            ->schema([
                                Forms\Components\Select::make('program_id')
                                    ->label('Program')
                                    ->options(Program::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select program...'),
                                Forms\Components\Select::make('student_status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Active',
                                        'on-leave' => 'On Leave',
                                        'leave-of-absence-approved' => 'Leave of Absence Approved',
                                        'absent-without-official-leave' => 'Absent Without Official Leave',
                                        'graduated' => 'Graduated',
                                        'dismissed' => 'Dismissed',
                                        'dropped' => 'Dropped',
                                        'withdrawn' => 'Withdrawn',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required(),
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
                            $this->buildAdviserRow('adviser', 'Primary Adviser'),
                            $this->buildAdviserRow('registration_adviser', 'Registration Adviser'),
                        ])->columns(2),
                    \Filament\Schemas\Components\Section::make('Advisory Committee')
                        ->schema([
                            Forms\Components\Repeater::make('committee_members')
                                ->label('Committee Members')
                                ->hiddenLabel()
                                ->schema([
                                    Forms\Components\Select::make('role')
                                        ->label('Role')
                                        ->options([
                                            'Chair' => 'Chair',
                                            'Co-Chair' => 'Co-Chair',
                                            'Cognate' => 'Cognate',
                                            'Major' => 'Major',
                                            'Minor' => 'Minor',
                                            'Member' => 'Member',
                                            'Adviser' => 'Adviser',
                                            'Co-Adviser' => 'Co-Adviser',
                                        ])
                                        ->required(),
                                    $this->buildFacultyIdSelect('faculty_id', 'Faculty Name')->required(),
                                    Forms\Components\DatePicker::make('appointed_date')
                                        ->label('Date Appointed'),
                                ])
                                ->columns(3)
                                ->columnSpanFull()
                                ->addActionLabel('Add Committee Member')
                                ->reorderable(false),
                        ]),
                ])
                    ->action(function (array $data): void {
                        $fullName = Student::buildFullName($data['surname'], $data['given_name'], $data['middle_name'] ?? null);
                        
                        // Extract non-student fields
                        $studentData = \Illuminate\Support\Arr::except($data, ['program_id', 'committee_members']);
                        
                        // Explicitly include program_id on the student record
                        if (!empty($data['program_id'])) {
                            $studentData['program_id'] = $data['program_id'];
                        }

                        $student = Student::create(array_merge($studentData, ['full_name' => $fullName]));

                        if (!empty($data['program_id'])) {
                            StudentProgram::create([
                                'student_id' => $student->id,
                                'program_id' => $data['program_id'],
                                'status' => 'active',
                            ]);
                        }

                        // Sync committee members
                        if (!empty($data['committee_members'])) {
                            foreach ($data['committee_members'] as $cm) {
                                if ($cm['faculty_id']) {
                                    $student->committeeMembers()->create([
                                        'faculty_id' => $cm['faculty_id'],
                                        'role' => $cm['role'],
                                        'appointed_date' => $cm['appointed_date'] ?? null,
                                    ]);
                                }
                            }
                        }

                        Notification::make()
                            ->title('Student Created')
                            ->body("{$fullName} has been added to the system.")
                            ->success()
                            ->duration(5000)
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50, 100]);
    }

}
