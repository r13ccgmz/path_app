<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrolleeResource\Pages;
use App\Imports\EnrolleeImport;
use App\Models\EnrollmentCourse;
use App\Models\Enrollee;
use App\Models\ImportLog;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class EnrolleeResource extends Resource
{
    protected static ?string $model = Enrollee::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';


    protected static string | \UnitEnum | null $navigationGroup = 'Student Management';

    protected static ?int $navigationSort = 2;


    protected static ?string $navigationLabel = 'List of Enrollees';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\TextInput::make('term_id')->label('Term ID')->required(),
                        Forms\Components\TextInput::make('student_number')->label('Student Number')->required(),
                        Forms\Components\TextInput::make('last_name')->label('Last Name')->required(),
                        Forms\Components\TextInput::make('first_name')->label('First Name')->required(),
                        Forms\Components\TextInput::make('middle_name')->label('Middle Name'),
                        Forms\Components\TextInput::make('degree_program')->label('Degree/Program'),
                        Forms\Components\Select::make('enrollmentCourses')
                            ->label('Courses Enrolled')
                            ->relationship('enrollmentCourses', 'course_code')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('course_code')
                                    ->label('Course Code')
                                    ->required()
                                    ->placeholder('e.g. CED 210'),
                            ])
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('total_units')->label('Total Units')->numeric(),
                        Forms\Components\TextInput::make('sex')->label('Sex'),
                        Forms\Components\TextInput::make('marital_status')->label('Marital Status'),
                        Forms\Components\DatePicker::make('birthdate')->label('Birthdate'),
                        Forms\Components\TextInput::make('nationality')->label('Nationality'),
                        Forms\Components\TextInput::make('email')->label('Email')->email(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('term_id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('term_id')
                    ->label('Term')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Student Number')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => Enrollee::formatStudentNumber($state))
                    ->url(fn (Enrollee $record): string =>
                        '/admin/list-of-students?studentNumber=' . urlencode($record->student_number)
                    )
                    ->color('primary'),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->label('Middle Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('degree_program')
                    ->label('Degree/Program')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('enrollmentCourses.course_code')
                    ->label('Courses')
                    ->badge()
                    ->wrap()
                    ->grow()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_units')
                    ->label('Units')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sex')
                    ->label('Sex')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('term_id')
                    ->label('Term')
                    ->multiple()
                    ->options(fn () => \App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                        ->get()
                        ->mapWithKeys(fn ($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
                        ->toArray())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('degree_program')
                    ->label('Degree/Program')
                    ->options(fn () => Enrollee::distinct()->whereNotNull('degree_program')->where('degree_program', '!=', '')->pluck('degree_program', 'degree_program')->toArray())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Raw GS Import Data')
                    ->modalDescription('This is raw enrollment data imported from the Graduate School. Edit student details via List of Students.')
                    ->form([
                        \Filament\Schemas\Components\Section::make('Student Information')
                            ->schema([
                                Forms\Components\TextInput::make('term_id')->label('Term ID')->disabled(),
                                Forms\Components\TextInput::make('student_number')->label('Student Number')->disabled(),
                                Forms\Components\TextInput::make('last_name')->label('Last Name')->disabled(),
                                Forms\Components\TextInput::make('first_name')->label('First Name')->disabled(),
                                Forms\Components\TextInput::make('middle_name')->label('Middle Name')->disabled(),
                                Forms\Components\TextInput::make('degree_program')->label('Degree/Program')->disabled(),
                                Forms\Components\TextInput::make('total_units')->label('Total Units')->disabled(),
                                Forms\Components\TextInput::make('sex')->label('Sex')->disabled(),
                                Forms\Components\TextInput::make('marital_status')->label('Marital Status')->disabled(),
                                Forms\Components\TextInput::make('email')->label('Email')->disabled(),
                                Forms\Components\TextInput::make('nationality')->label('Nationality')->disabled(),
                                Forms\Components\TextInput::make('courses_enrolled')->label('Courses Enrolled (raw)')->disabled()->columnSpanFull(),
                            ])->columns(3),
                    ]),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->headerActions([
                Action::make('uploadExcel')
                    ->label('Upload Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('Excel File')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->required()
                            ->disk('local')
                            ->directory('imports')
                            ->storeFileNamesIn('original_filename'),
                    ])
                    ->action(function (array $data) {
                        $filePath = storage_path('app/private/' . $data['file']);
                        if (!file_exists($filePath)) {
                            $filePath = storage_path('app/' . $data['file']);
                        }

                        $import = new EnrolleeImport();

                        set_time_limit(0);
                        ini_set('memory_limit', '1024M');

                        try {
                            Excel::import($import, $filePath);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        $resultsFileName = 'import_results_' . now()->format('Y_m_d_His') . '.xlsx';

                        Excel::store(
                            new \App\Exports\ImportResultsExport($import->getProcessedResults()),
                            $resultsFileName,
                            'local'
                        );

                        $importLog = ImportLog::create([
                            'filename' => $data['original_filename'] ?? basename($data['file']),
                            'rows_imported' => $import->getImportedCount(),
                            'rows_updated' => $import->getUpdatedCount(),
                            'rows_rejected' => $import->getRejectedCount() + $import->getSkippedBlankCount(),
                            'rows_unchanged' => $import->getSkippedCount(),
                            'errors' => $import->getErrorDetails(),
                            'user_id' => Auth::id(),
                            'results_file' => $resultsFileName,
                        ]);

                        $pivotData = [];
                        foreach ($import->getAffectedRows() as $row) {
                            $pivotData[$row['id']] = ['action' => $row['action']];
                        }
                        if (!empty($pivotData)) {
                            $importLog->enrollees()->attach($pivotData);
                        }

                        $parts = [];
                        if ($import->getImportedCount() > 0) {
                            $parts[] = $import->getImportedCount() . ' imported';
                        }
                        if ($import->getUpdatedCount() > 0) {
                            $parts[] = $import->getUpdatedCount() . ' updated';
                        }
                        if ($import->getSkippedCount() > 0) {
                            $parts[] = $import->getSkippedCount() . ' unchanged';
                        }
                        if ($import->getRejectedCount() + $import->getSkippedBlankCount() > 0) {
                            $parts[] = ($import->getRejectedCount() + $import->getSkippedBlankCount()) . ' rejected';
                        }

                        $message = implode(' · ', $parts);

                        if ($import->getRejectedCount() + $import->getSkippedBlankCount() > 0) {
                            Notification::make()
                                ->title('Import Completed with Warnings')
                                ->body($message . '. Check Import Logs for details.')
                                ->warning()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Successful')
                                ->body($message)
                                ->success()
                                ->duration(8000)
                                ->send();
                        }

                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->schema([
                        Forms\Components\CheckboxList::make('term_ids')
                            ->label('Select Terms')
                            ->options(fn () => Enrollee::distinct()->orderBy('term_id', 'desc')->pluck('term_id', 'term_id')->toArray())
                            ->required()
                            ->columns(4)
                            ->gridDirection('row')
                            ->searchable()
                            ->bulkToggleable(),
                        Forms\Components\CheckboxList::make('programs')
                            ->label('Filter by Program (optional)')
                            ->options(fn () => Enrollee::distinct()
                                ->whereNotNull('degree_program')
                                ->where('degree_program', '!=', '')
                                ->orderBy('degree_program')
                                ->pluck('degree_program', 'degree_program')
                                ->toArray())
                            ->columns(1)
                            ->searchable()
                            ->bulkToggleable(),
                    ])
                    ->action(function (array $data) {
                        $termIds = $data['term_ids'] ?? [];
                        $programs = $data['programs'] ?? [];

                        return Excel::download(
                            new \App\Exports\EnrollmentProgramExport($programs, $termIds),
                            'enrollees_' . now()->format('Y_m_d') . '.xlsx'
                        );
                    }),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollees::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
