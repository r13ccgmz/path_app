<?php

namespace App\Filament\Pages;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use App\Models\EnrollmentCourse;
use App\Models\Enrollee;
use App\Models\Graduate;
use App\Models\NormalizationRule;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Services\ProgramMatcher;
use App\Support\NameNormalizer;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class Normalizations extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string | \UnitEnum | null $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Normalizations';
    protected static ?string $title = 'Normalization Rules';
    protected static ?int $navigationSort = 5;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $slug = 'normalizations';
    protected string $view = 'filament.pages.normalizations';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\RawProgramsWidget::class,
            \App\Filament\Widgets\RawCoursesWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    /**
     * All 4 normalization rule types with labels.
     */
    private static function ruleTypeOptions(): array
    {
        return [
            'program' => 'Enrollee — Program Name',
            'course_code' => 'Enrollee — Course Code',
            'degree' => 'Graduate — Degree',
            'major_field' => 'Graduate — Major / Field',
            'program_name' => 'Graduate — Program Name',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyToExisting')
                ->label('Apply to Existing Data')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Apply Normalization Rules')
                ->modalDescription('This will update all existing enrollee AND graduate records using the current normalization rules. Program changes will also propagate to linked students. This action cannot be undone.')
                ->action(function () {
                    $programRules = NormalizationRule::getMap('program');
                    $courseCodeRules = NormalizationRule::getMap('course_code');
                    $degreeRules = NormalizationRule::getMap('degree');
                    $majorFieldRules = NormalizationRule::getMap('major_field');

                    $programCount = 0;
                    $courseCodeCount = 0;
                    $courseTextCount = 0;
                    $degreeCount = 0;
                    $majorFieldCount = 0;
                    $studentCount = 0;

                    // ── 1. Apply enrollee program normalizations ──
                    foreach ($programRules as $from => $to) {
                        $affected = DB::table('enrollees')
                            ->whereRaw('LOWER(degree_program) = ?', [strtolower($from)])
                            ->update(['degree_program' => $to]);
                        $programCount += $affected;
                    }

                    // ── 2. Apply course code normalizations ──
                    foreach ($courseCodeRules as $from => $to) {
                        // 2a. Normalize enrollment_courses table
                        $fromCourse = EnrollmentCourse::where('course_code', $from)->first();
                        $toCourse = EnrollmentCourse::where('course_code', $to)->first();

                        if ($fromCourse) {
                            if ($toCourse) {
                                // Merge: move pivot records from old course to new course (skip duplicates)
                                DB::table('enrollment_course_enrollee')
                                    ->where('enrollment_course_id', $fromCourse->id)
                                    ->whereNotIn('enrollee_id', function ($q) use ($toCourse) {
                                        $q->select('enrollee_id')
                                            ->from('enrollment_course_enrollee')
                                            ->where('enrollment_course_id', $toCourse->id);
                                    })
                                    ->update(['enrollment_course_id' => $toCourse->id]);

                                // Delete remaining duplicates and old course
                                DB::table('enrollment_course_enrollee')
                                    ->where('enrollment_course_id', $fromCourse->id)
                                    ->delete();
                                $fromCourse->delete();
                            } else {
                                // Just rename
                                $fromCourse->update(['course_code' => $to]);
                            }
                            $courseCodeCount++;
                        }

                        // 2b. Normalize the courses_enrolled text field INDEPENDENTLY
                        // (This was previously nested inside the if($fromCourse) block, causing it to be skipped)
                        $textAffected = DB::table('enrollees')
                            ->whereRaw('courses_enrolled LIKE ?', ['%' . $from . '%'])
                            ->update([
                                'courses_enrolled' => DB::raw(
                                    'REPLACE(courses_enrolled, ' . DB::getPdo()->quote($from) . ', ' . DB::getPdo()->quote($to) . ')'
                                ),
                            ]);
                        $courseTextCount += $textAffected;
                    }

                    // ── 2c. Clean up trailing punctuation artifacts in courses_enrolled ──
                    // After REPLACE, entries like "RESIDENCY ." can remain. Strip trailing dots/whitespace from each CSV entry.
                    $dirtyRecords = DB::table('enrollees')
                        ->whereRaw('courses_enrolled REGEXP ?', ['[[:space:]]+\\.|\\.[[:space:]]*,|\\.[[:space:]]*$'])
                        ->get(['id', 'courses_enrolled']);
                    foreach ($dirtyRecords as $record) {
                        $entries = array_map('trim', explode(',', $record->courses_enrolled));
                        $cleaned = array_values(array_filter(array_map(function ($entry) {
                            // Strip trailing dots, whitespace, and leading/trailing whitespace
                            return trim(rtrim(trim($entry), '. '));
                        }, $entries)));
                        $newValue = implode(', ', $cleaned);
                        if ($newValue !== $record->courses_enrolled) {
                            DB::table('enrollees')->where('id', $record->id)->update(['courses_enrolled' => $newValue]);
                            $courseTextCount++;
                        }
                    }

                    // ── 3. Apply graduate degree normalizations ──
                    foreach ($degreeRules as $from => $to) {
                        $affected = DB::table('graduates')
                            ->whereRaw('UPPER(TRIM(degree)) = ?', [strtoupper(trim($from))])
                            ->update(['degree' => $to]);
                        $degreeCount += $affected;
                    }

                    // ── 4. Apply graduate major_field normalizations ──
                    foreach ($majorFieldRules as $from => $to) {
                        $affected = DB::table('graduates')
                            ->whereRaw('LOWER(TRIM(major_field_raw)) = ?', [strtolower(trim($from))])
                            ->update(['major_field_raw' => $to]);
                        $majorFieldCount += $affected;
                    }

                    // ── 4b. Apply graduate program_name normalizations ──
                    $programNameRules = NormalizationRule::getMap('program_name');
                    $programNameCount = 0;
                    foreach ($programNameRules as $from => $to) {
                        $affected = DB::table('graduates')
                            ->whereRaw('LOWER(TRIM(program_name)) = ?', [strtolower(trim($from))])
                            ->update(['program_name' => $to]);
                        $programNameCount += $affected;
                    }

                    // ── 5. Re-resolve program_id on affected graduates ──
                    if ($degreeCount > 0 || $majorFieldCount > 0) {
                        $this->reResolveGraduatePrograms();
                    }

                    // ── 6. Propagate program changes to students ──
                    if ($programCount > 0) {
                        $studentCount = $this->propagateProgramChangesToStudents();
                    }

                    // ── Build summary ──
                    $parts = [];
                    if ($programCount > 0) $parts[] = "{$programCount} enrollee programs";
                    if ($courseCodeCount > 0) $parts[] = "{$courseCodeCount} course code entries";
                    if ($courseTextCount > 0) $parts[] = "{$courseTextCount} course text fields";
                    if ($degreeCount > 0) $parts[] = "{$degreeCount} graduate degrees";
                    if ($majorFieldCount > 0) $parts[] = "{$majorFieldCount} graduate major fields";
                    if ($programNameCount > 0) $parts[] = "{$programNameCount} graduate program names";
                    if ($studentCount > 0) $parts[] = "{$studentCount} student program links";

                    $summary = !empty($parts) ? implode(', ', $parts) . ' updated.' : 'No records matched the current rules.';

                    Notification::make()
                        ->title('Normalization Applied')
                        ->body($summary)
                        ->success()
                        ->duration(10000)
                        ->send();
                }),

            \Filament\Actions\ActionGroup::make([
                Action::make('normalizeNames')
                    ->label('Normalize Names')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Normalize Name Capitalization')
                    ->modalDescription('This will convert ALL CAPS names in enrollees, graduates, and students tables to proper Title Case (e.g., "DELA CRUZ" → "Dela Cruz"). This action cannot be undone.')
                    ->action(function () {
                        $enrolleeCount = 0;
                        $graduateCount = 0;
                        $studentCount = 0;

                        // Normalize enrollee names
                        Enrollee::whereRaw('last_name = UPPER(last_name) AND last_name != \'\'')->chunk(200, function ($enrollees) use (&$enrolleeCount) {
                            foreach ($enrollees as $enrollee) {
                                $newLast = NameNormalizer::normalize($enrollee->last_name);
                                $newFirst = NameNormalizer::normalize($enrollee->first_name);
                                $newMiddle = NameNormalizer::normalize($enrollee->middle_name);
                                if ($newLast !== $enrollee->last_name || $newFirst !== $enrollee->first_name || $newMiddle !== $enrollee->middle_name) {
                                    $enrollee->update(['last_name' => $newLast, 'first_name' => $newFirst, 'middle_name' => $newMiddle]);
                                    $enrolleeCount++;
                                }
                            }
                        });

                        // Normalize graduate names
                        Graduate::whereRaw('name = UPPER(name) AND name != \'\'')->chunk(200, function ($graduates) use (&$graduateCount) {
                            foreach ($graduates as $graduate) {
                                $newName = NameNormalizer::normalizeFullName($graduate->name);
                                if ($newName !== $graduate->name) {
                                    $graduate->update([
                                        'name' => $newName,
                                        'chair' => NameNormalizer::normalize($graduate->chair),
                                        'co_chair' => NameNormalizer::normalize($graduate->co_chair),
                                        'member1' => NameNormalizer::normalize($graduate->member1),
                                        'member2' => NameNormalizer::normalize($graduate->member2),
                                        'member3' => NameNormalizer::normalize($graduate->member3),
                                        'member4' => NameNormalizer::normalize($graduate->member4),
                                        'member5' => NameNormalizer::normalize($graduate->member5),
                                        'major_field_raw' => NameNormalizer::normalize($graduate->major_field_raw),
                                    ]);
                                    $graduateCount++;
                                }
                            }
                        });

                        // Normalize student names
                        Student::whereRaw('surname = UPPER(surname) AND surname != \'\'')->chunk(200, function ($students) use (&$studentCount) {
                            foreach ($students as $student) {
                                $newSurname = NameNormalizer::normalize($student->surname);
                                $newGiven = NameNormalizer::normalize($student->given_name);
                                $newMiddle = NameNormalizer::normalize($student->middle_name);
                                $newFull = Student::buildFullName($newSurname, $newGiven, $newMiddle);
                                if ($newSurname !== $student->surname || $newGiven !== $student->given_name) {
                                    $student->update(['surname' => $newSurname, 'given_name' => $newGiven, 'middle_name' => $newMiddle, 'full_name' => $newFull]);
                                    $studentCount++;
                                }
                            }
                        });

                        Notification::make()
                            ->title('Name Normalization Complete')
                            ->body("{$enrolleeCount} enrollees, {$graduateCount} graduates, {$studentCount} students normalized to Title Case.")
                            ->success()
                            ->duration(8000)
                            ->send();
                    }),

                Action::make('seedDegreeRules')
                    ->label('Seed Degree Rules')
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Seed Default Degree Normalization Rules')
                    ->modalDescription('This will add standard degree abbreviation mappings (e.g. PH.D. → PhD) to the rules table. Existing rules will not be duplicated.')
                    ->action(function () {
                        $defaults = [
                            ['type' => 'degree', 'from_value' => 'PHD', 'to_value' => 'PhD'],
                            ['type' => 'degree', 'from_value' => 'PH.D', 'to_value' => 'PhD'],
                            ['type' => 'degree', 'from_value' => 'PH.D.', 'to_value' => 'PhD'],
                            ['type' => 'degree', 'from_value' => 'DOCTOR OF PHILOSOPHY', 'to_value' => 'PhD'],
                            ['type' => 'degree', 'from_value' => 'MS', 'to_value' => 'MS'],
                            ['type' => 'degree', 'from_value' => 'M.S', 'to_value' => 'MS'],
                            ['type' => 'degree', 'from_value' => 'M.S.', 'to_value' => 'MS'],
                            ['type' => 'degree', 'from_value' => 'MA', 'to_value' => 'MA'],
                            ['type' => 'degree', 'from_value' => 'M.A', 'to_value' => 'MA'],
                            ['type' => 'degree', 'from_value' => 'M.A.', 'to_value' => 'MA'],
                            ['type' => 'degree', 'from_value' => 'MPA', 'to_value' => 'MPA'],
                            ['type' => 'degree', 'from_value' => 'M.P.A', 'to_value' => 'MPA'],
                            ['type' => 'degree', 'from_value' => 'MPAF', 'to_value' => 'MPAf'],
                            ['type' => 'degree', 'from_value' => 'M.P.AF', 'to_value' => 'MPAf'],
                            ['type' => 'degree', 'from_value' => 'MPAEM', 'to_value' => 'MPAEM'],
                            ['type' => 'degree', 'from_value' => 'DPA', 'to_value' => 'DPA'],
                            ['type' => 'degree', 'from_value' => 'DMG', 'to_value' => 'MDMG'],
                            ['type' => 'degree', 'from_value' => 'MDMG', 'to_value' => 'MDMG'],
                        ];

                        $seeded = 0;
                        foreach ($defaults as $rule) {
                            $exists = NormalizationRule::where('type', $rule['type'])
                                ->where('from_value', $rule['from_value'])
                                ->exists();
                            if (!$exists) {
                                NormalizationRule::create($rule);
                                $seeded++;
                            }
                        }

                        Notification::make()
                            ->title($seeded > 0 ? "{$seeded} degree rules seeded" : 'All rules already exist')
                            ->color($seeded > 0 ? 'success' : 'info')
                            ->duration(5000)
                            ->send();
                    }),
            ])
            ->label('Tools')
            ->icon('heroicon-m-wrench-screwdriver')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Re-resolve program_id on graduates after degree/major normalization.
     */
    private function reResolveGraduatePrograms(): void
    {
        $programs = \App\Models\Program::all();
        $programMajors = \App\Models\ProgramMajor::all();

        Graduate::whereNull('program_id')
            ->orWhereNotNull('program_id') // re-resolve all
            ->chunk(200, function ($graduates) use ($programs, $programMajors) {
                foreach ($graduates as $graduate) {
                    $degree = $graduate->degree;
                    $majorField = $graduate->major_field_raw;

                    if (empty($degree)) continue;

                    $resolved = $this->resolveGraduateProgram($degree, $majorField ?? '', $programs, $programMajors);

                    if ($resolved['program_id'] && $resolved['program_id'] !== $graduate->program_id) {
                        $graduate->update([
                            'program_id' => $resolved['program_id'],
                            'program_name' => $resolved['program_name'],
                            'major' => $resolved['major'],
                        ]);
                    }
                }
            });
    }

    /**
     * Simplified program resolution for graduates (mirrors GraduateImport::resolveProgram).
     */
    private function resolveGraduateProgram(string $degree, string $majorField, $programs, $programMajors): array
    {
        $result = ['program_id' => null, 'program_name' => null, 'major' => null];
        $majorLower = mb_strtolower(trim($majorField));

        // For generic degrees, match by major field
        if (in_array($degree, ['PhD', 'MS', 'MA'])) {
            $codePrefix = mb_strtolower($degree);
            foreach ($programs as $program) {
                $codeLower = mb_strtolower($program->code);
                $progNameLower = mb_strtolower($program->name);
                if (str_starts_with($codeLower, $codePrefix) && !empty($majorLower) && str_contains($progNameLower, $majorLower)) {
                    $result['program_id'] = $program->id;
                    $result['program_name'] = $program->name;
                    if (!empty($majorField)) {
                        $matchedMajor = $programMajors->first(fn ($m) =>
                            $m->program_id === $program->id && mb_strtolower($m->name) === $majorLower
                        );
                        if ($matchedMajor) $result['major'] = $matchedMajor->name;
                    }
                    return $result;
                }
            }
            return $result;
        }

        // For specific degrees, match by code
        $program = $programs->first(fn ($p) => strcasecmp($p->code, $degree) === 0);
        if ($program) {
            $result['program_id'] = $program->id;
            $result['program_name'] = $program->name;
            if (!empty($majorField)) {
                $matchedMajor = $programMajors->first(fn ($m) =>
                    $m->program_id === $program->id && mb_strtolower($m->name) === $majorLower
                );
                if ($matchedMajor) $result['major'] = $matchedMajor->name;
            }
        }

        return $result;
    }

    /**
     * Propagate program changes from enrollees to linked students/student_programs.
     */
    private function propagateProgramChangesToStudents(): int
    {
        $matcher = ProgramMatcher::instance();
        $updated = 0;

        // Get all distinct student numbers from enrollees that have linked students
        $studentNumbers = Student::pluck('student_number')->toArray();
        if (empty($studentNumbers)) return 0;

        foreach (array_chunk($studentNumbers, 100) as $chunk) {
            $students = Student::whereIn('student_number', $chunk)->get();
            foreach ($students as $student) {
                $latest = Enrollee::where('student_number', $student->student_number)
                    ->orderByDesc('term_id')
                    ->first();

                if (!$latest || empty($latest->degree_program)) continue;

                $newProgramId = $matcher->match($latest->degree_program);
                if ($newProgramId && $newProgramId !== $student->program_id) {
                    $student->update(['program_id' => $newProgramId]);

                    // Also update student_programs raw_degree_name
                    StudentProgram::where('student_id', $student->id)
                        ->whereNotNull('raw_degree_name')
                        ->update(['raw_degree_name' => $latest->degree_program]);

                    $updated++;
                }
            }
        }

        return $updated;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(NormalizationRule::query())
            ->defaultSort('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'program' => 'primary',
                        'course_code' => 'warning',
                        'degree' => 'success',
                        'major_field' => 'info',
                        'program_name' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'program' => 'Enrollee — Program',
                        'course_code' => 'Enrollee — Course Code',
                        'degree' => 'Graduate — Degree',
                        'major_field' => 'Graduate — Major',
                        'program_name' => 'Graduate — Program Name',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('from_value')
                    ->label('From')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('to_value')
                    ->label('To')
                    ->searchable()
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(self::ruleTypeOptions()),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add Rule')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(self::ruleTypeOptions())
                            ->required(),
                        Forms\Components\TextInput::make('from_value')
                            ->label('From Value')
                            ->required(),
                        Forms\Components\TextInput::make('to_value')
                            ->label('To Value')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        NormalizationRule::create($data);
                        Notification::make()
                            ->title('Rule added')
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->fillForm(fn (NormalizationRule $record): array => $record->toArray())
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(self::ruleTypeOptions())
                            ->required(),
                        Forms\Components\TextInput::make('from_value')
                            ->label('From Value')
                            ->required(),
                        Forms\Components\TextInput::make('to_value')
                            ->label('To Value')
                            ->required(),
                    ])
                    ->action(function (NormalizationRule $record, array $data): void {
                        $record->update($data);
                        Notification::make()
                            ->title('Rule updated')
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (NormalizationRule $record) => $record->delete()),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
