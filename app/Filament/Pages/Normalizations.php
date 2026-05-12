<?php

namespace App\Filament\Pages;

use App\Models\EnrollmentCourse;
use App\Models\Enrollee;
use App\Models\Graduate;
use App\Models\NormalizationRule;
use App\Models\Student;
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
    use InteractsWithTable;

    protected static string | \UnitEnum | null $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Normalizations';
    protected static ?string $title = 'Normalization Rules';
    protected static ?int $navigationSort = 4;
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyToExisting')
                ->label('Apply to Existing Data')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Apply Normalization Rules')
                ->modalDescription('This will update all existing enrollee records using the current normalization rules. This action cannot be undone.')
                ->action(function () {
                    $programRules = NormalizationRule::getMap('program');
                    $courseCodeRules = NormalizationRule::getMap('course_code');

                    $programCount = 0;
                    $courseCodeCount = 0;

                    // Apply program normalizations
                    foreach ($programRules as $from => $to) {
                        $affected = DB::table('enrollees')
                            ->whereRaw('LOWER(degree_program) = ?', [strtolower($from)])
                            ->update(['degree_program' => $to]);
                        $programCount += $affected;
                    }

                    // Apply course code normalizations
                    foreach ($courseCodeRules as $from => $to) {
                        // Normalize enrollment_courses table
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

                        // Also normalize the text field
                        DB::table('enrollees')
                            ->whereRaw('courses_enrolled LIKE ?', ["%{$from}%"])
                            ->update([
                                'courses_enrolled' => DB::raw("REPLACE(courses_enrolled, '{$from}', '{$to}')"),
                            ]);
                    }

                    Notification::make()
                        ->title('Normalization Applied')
                        ->body("{$programCount} program records updated. {$courseCodeCount} course code rules applied.")
                        ->success()
                        ->duration(8000)
                        ->send();
                }),

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
        ];
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
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'program' => 'Program',
                        'course_code' => 'Course Code',
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
                    ->options([
                        'program' => 'Program Name',
                        'course_code' => 'Course Code',
                    ]),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add Rule')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'program' => 'Program Name',
                                'course_code' => 'Course Code',
                            ])
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
                            ->options([
                                'program' => 'Program Name',
                                'course_code' => 'Course Code',
                            ])
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
