<?php

namespace App\Filament\Pages;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use App\Models\CourseOffering;
use App\Models\Faculty;
use App\Models\Semester;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FacultyWorkload extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|\UnitEnum|null $navigationGroup = 'Faculty Management';
    protected static ?int $navigationSort = 3;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Faculty Workload';
    protected static ?string $title = 'Faculty Workload';
    protected static ?string $slug = 'faculty-workload';

    protected string $view = 'filament.pages.faculty-workload';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\FacultyCourseLoadChartWidget::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $semesterFilter = $this->getTableFilterState('semester')['values'] ?? [];

                $query = Faculty::query()
                    ->where('is_external', false);

                if (!empty($semesterFilter)) {
                    $query->withCount(['courseOfferings' => function ($q) use ($semesterFilter) {
                        $q->whereIn('semester_id', $semesterFilter);
                    }]);
                } else {
                    $query->withCount(['courseOfferings']);
                }

                $query->addSelect([
                    'faculty.*',
                    DB::raw('(SELECT COUNT(DISTINCT co.semester_id) FROM course_offerings co WHERE co.faculty_id = faculty.id' . 
                        (!empty($semesterFilter) ? ' AND co.semester_id IN (' . implode(',', array_map('intval', $semesterFilter)) . ')' : '') . 
                        ') as semesters_teaching'),
                ]);

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Faculty Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name'])
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                Tables\Columns\TextColumn::make('designation')
                    ->label('Academic Rank')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('unit.code')
                    ->label('Unit')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('course_offerings_count')
                    ->label('Total Courses')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state >= 10 => 'success',
                        $state >= 5 => 'info',
                        $state >= 1 => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('semesters_teaching')
                    ->label('Semesters')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('semester')
                    ->label('Semester')
                    ->multiple()
                    ->options(fn () => Semester::with('academicYear')
                        ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                        ->get()
                        ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                        ->toArray()
                    )
                    ->query(function (Builder $query, array $data) {
                        $values = $data['values'] ?? [];
                        if (empty($values)) return $query;
                        return $query->whereHas('courseOfferings', fn ($q) => $q->whereIn('semester_id', $values));
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('unit')
                    ->label('Unit')
                    ->relationship('unit', 'code')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('last_name')
            ->emptyStateHeading('No faculty workload data')
            ->emptyStateDescription('Course offerings must be assigned to faculty members to see workload data.')
            ->emptyStateIcon('heroicon-o-briefcase')
            ->recordActions([
                \Filament\Actions\Action::make('manage_workload')
                    ->label('Manage Workload')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->modalWidth('4xl')
                    ->visible(fn () => !auth()->user()->hasRole('viewer'))
                    ->fillForm(function (Faculty $record) {
                        $defaultSemesterId = \App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')->first()?->id;
                        
                        $offerings = [];
                        if ($defaultSemesterId) {
                            $offerings = \App\Models\CourseOffering::where('faculty_id', $record->id)
                                ->where('semester_id', $defaultSemesterId)
                                ->get(['course_id', 'schedule', 'max_slots', 'remarks'])
                                ->toArray();
                        }
                        
                        return [
                            'semester_id' => $defaultSemesterId,
                            'courses' => $offerings,
                        ];
                    })
                    ->form([
                        \Filament\Schemas\Components\Grid::make(1)
                            ->schema([
                                \Filament\Forms\Components\Select::make('semester_id')
                                    ->label('Select Semester to Manage')
                                    ->options(fn () => Semester::with('academicYear')
                                        ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                                        ->get()
                                        ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
                                        ->toArray()
                                    )
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, Faculty $record) {
                                        if (!$state) {
                                            $set('courses', []);
                                            return;
                                        }
                                        
                                        $offerings = \App\Models\CourseOffering::where('faculty_id', $record->id)
                                            ->where('semester_id', $state)
                                            ->get(['course_id', 'schedule', 'max_slots', 'remarks'])
                                            ->toArray();
                                            
                                        $set('courses', $offerings);
                                    }),
                                    
                                \Filament\Forms\Components\Repeater::make('courses')
                                    ->label('Assigned Courses')
                                    ->addActionLabel('Assign Course')
                                    ->columns(2)
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('course_id')
                                            ->label('Course')
                                            ->options(fn () => \App\Models\Course::where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(fn ($c) => [$c->id => "[{$c->course_code}] {$c->course_name}"])
                                                ->toArray()
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        \Filament\Forms\Components\TextInput::make('schedule')
                                            ->label('Schedule / Class Time')
                                            ->placeholder('e.g., TTh 9:00 AM - 10:30 AM')
                                            ->maxLength(255),
                                        \Filament\Forms\Components\TextInput::make('max_slots')
                                            ->label('Max Slots')
                                            ->numeric()
                                            ->minValue(1)
                                            ->placeholder('e.g., 40'),
                                        \Filament\Forms\Components\TextInput::make('remarks')
                                            ->label('Remarks')
                                            ->maxLength(500),
                                    ])
                                    ->default([]),
                            ])
                    ])
                    ->action(function (Faculty $record, array $data) {
                        $semesterId = $data['semester_id'];
                        $submittedCourses = $data['courses'] ?? [];
                        
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $semesterId, $submittedCourses) {
                            // 1. Dissociate (set faculty_id = null) all course offerings currently assigned to this faculty member for this semester
                            \App\Models\CourseOffering::where('faculty_id', $record->id)
                                ->where('semester_id', $semesterId)
                                ->update(['faculty_id' => null]);
                                
                            // 2. Insert or update the submitted courses
                            foreach ($submittedCourses as $item) {
                                \App\Models\CourseOffering::updateOrCreate(
                                    [
                                        'course_id' => $item['course_id'],
                                        'semester_id' => $semesterId,
                                    ],
                                    [
                                        'faculty_id' => $record->id,
                                        'schedule' => $item['schedule'] ?? null,
                                        'max_slots' => $item['max_slots'] ?? null,
                                        'remarks' => $item['remarks'] ?? null,
                                    ]
                                );
                            }
                        });
                        
                        $this->dispatch('workload-changed');
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Workload updated successfully')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\Action::make('view_offerings')
                    ->label('View Courses')
                    ->icon('heroicon-o-book-open')
                    ->color('info')
                    ->modalHeading(fn (Faculty $record) => "Courses Taught by {$record->full_name}")
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(function (Faculty $record) {
                        $semesterFilter = $this->getTableFilterState('semester')['values'] ?? [];
                        
                        $offerings = $record->courseOfferings()
                            ->join('semesters', 'course_offerings.semester_id', '=', 'semesters.id')
                            ->orderByRaw('CAST(semesters.term_code AS UNSIGNED) DESC')
                            ->select('course_offerings.*')
                            ->with(['course', 'semester']);
                            
                        if (!empty($semesterFilter)) {
                            $offerings->whereIn('course_offerings.semester_id', $semesterFilter);
                        }
                        
                        $offerings = $offerings->get();
                        
                        if ($offerings->isEmpty()) {
                            return [
                                \Filament\Forms\Components\Placeholder::make('no_courses')
                                    ->hiddenLabel()
                                    ->content('No courses assigned for the selected term(s).'),
                            ];
                        }
                        
                        $grouped = $offerings->groupBy('semester_id');
                        $sections = [];
                        
                        foreach ($grouped as $semesterId => $items) {
                            $semester = $items->first()->semester;
                            $termText = "[{$semester->term_code}] {$semester->label}";
                            
                            $courseFields = [];
                            foreach ($items as $offering) {
                                $courseText = "[{$offering->course->course_code}] {$offering->course->course_name}";
                                
                                $details = [];
                                if ($offering->max_slots) {
                                    $details[] = "Max Slots: {$offering->max_slots}";
                                }
                                if ($offering->remarks) {
                                    $details[] = "Remarks: {$offering->remarks}";
                                }
                                $detailsText = !empty($details) ? implode(' | ', $details) : 'None';
                                
                                $courseFields[] = \Filament\Schemas\Components\Grid::make(3)
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make("course_{$offering->id}")
                                            ->label('Course')
                                            ->content($courseText),
                                        \Filament\Forms\Components\Placeholder::make("schedule_{$offering->id}")
                                            ->label('Schedule / Time')
                                            ->content($offering->schedule ?? 'Not set'),
                                        \Filament\Forms\Components\Placeholder::make("details_{$offering->id}")
                                            ->label('Details')
                                            ->content($detailsText),
                                    ]);
                            }
                            
                            $sections[] = \Filament\Schemas\Components\Section::make($termText)
                                ->schema($courseFields)
                                ->collapsible();
                        }
                        
                        return $sections;
                    })
            ])
            ->paginated([10, 25, 50, 100]);
    }

    public function getWorkloadStats(): array
    {
        $semesterFilter = $this->getTableFilterState('semester')['values'] ?? [];

        $query = CourseOffering::query();
        if (!empty($semesterFilter)) {
            $query->whereIn('semester_id', $semesterFilter);
        }

        $totalOfferings = (clone $query)->count();
        $uniqueFaculty = (clone $query)->whereNotNull('faculty_id')->distinct('faculty_id')->count('faculty_id');
        $avgPerFaculty = $uniqueFaculty > 0 ? round($totalOfferings / $uniqueFaculty, 1) : 0;

        $facultyWithNoCourses = Faculty::where('is_external', false)
            ->whereDoesntHave('courseOfferings', function ($q) use ($semesterFilter) {
                if (!empty($semesterFilter)) {
                    $q->whereIn('semester_id', $semesterFilter);
                }
            })->count();

        return [
            'totalOfferings' => $totalOfferings,
            'uniqueFaculty' => $uniqueFaculty,
            'avgPerFaculty' => $avgPerFaculty,
            'noCourseFaculty' => $facultyWithNoCourses,
        ];
    }
}
