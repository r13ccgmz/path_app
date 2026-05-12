<?php

namespace App\Filament\Pages;

use App\Imports\GraduateImport;
use App\Models\Enrollee;
use App\Models\Graduate;
use App\Services\GraduateMatchService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListOfGraduates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \UnitEnum | null $navigationGroup = 'Student Management';
    protected static ?string $navigationLabel = 'List of Graduates';
    protected static ?string $title = 'Graduates';
    protected static ?int $navigationSort = 3;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';
    protected string $view = 'filament.pages.list-of-graduates';

    public array $proposals = [];
    public array $selected = [];
    public int $matchThreshold = 80;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadExcel')
                ->label('Upload Graduates')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->schema([
                    Forms\Components\FileUpload::make('file')
                        ->label('Excel / CSV File')
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

                    $originalFilename = $data['original_filename'] ?? basename($data['file']);
                    $import = new GraduateImport($originalFilename);

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

                    // Finalize: creates the import log with per-record details
                    $import->finalize();

                    Notification::make()
                        ->title('Graduate Import Complete')
                        ->body($import->getImportedCount() . ' imported · ' . $import->getUpdatedCount() . ' updated · ' . $import->getSkippedCount() . ' skipped')
                        ->success()
                        ->duration(8000)
                        ->send();

                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    // Scan for matches and open the review modal
                    $this->proposals = GraduateMatchService::scanAll($this->matchThreshold);

                    if (!empty($this->proposals)) {
                        $this->selected = array_fill(0, count($this->proposals), true);
                        $this->js('setTimeout(() => { $wire.mountAction("reviewMatches") }, 500)');
                    } else {
                        Notification::make()
                            ->title('No new matches found')
                            ->body("All graduates are either already matched or no candidates above {$this->matchThreshold}% threshold were found.")
                            ->info()
                            ->duration(5000)
                            ->send();
                    }
                }),

            Action::make('autoMatch')
                ->label('Auto-Match')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->schema([
                    Forms\Components\TextInput::make('threshold')
                        ->label('Similarity Threshold (%)')
                        ->numeric()
                        ->default(80)
                        ->minValue(50)
                        ->maxValue(100)
                        ->step(5)
                        ->helperText('Lower = more matches but less accurate. Recommended: 80–90%.'),
                ])
                ->action(function (array $data) {
                    $threshold = (int) ($data['threshold'] ?? 80);
                    $this->matchThreshold = $threshold;
                    $this->proposals = GraduateMatchService::scanAll($threshold);

                    if (!empty($this->proposals)) {
                        $this->selected = array_fill(0, count($this->proposals), true);
                        $this->js('setTimeout(() => { $wire.mountAction("reviewMatches") }, 500)');
                    } else {
                        Notification::make()
                            ->title('No matches found')
                            ->body("No candidates above {$threshold}% threshold were found.")
                            ->info()
                            ->duration(5000)
                            ->send();
                    }
                }),

            Action::make('reviewMatches')
                ->label('Quick Match')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->extraAttributes(['class' => 'hidden'])
                ->modalHeading('')
                ->modalWidth('7xl')
                ->modalContent(fn () => view('filament.pages.partials.match-proposals', [
                    'proposals' => $this->proposals,
                    'selected' => $this->selected,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelAction(false),

            Action::make('removeAllAutoLinks')
                ->label('Remove All Auto Links')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Remove All Auto-Matched Links')
                ->modalDescription('This will clear the student_number for ALL graduates that were matched automatically. Manually-assigned matches will not be affected.')
                ->action(function () {
                    $count = Graduate::where('match_type', 'auto')
                        ->whereNotNull('student_number')
                        ->where('student_number', '!=', '')
                        ->update(['student_number' => null, 'match_type' => null]);

                    Notification::make()
                        ->title('Auto-links removed')
                        ->body("{$count} auto-matched graduate links have been cleared.")
                        ->success()
                        ->duration(5000)
                        ->send();
                }),
        ];
    }

    public function toggleAll(): void
    {
        $allSelected = !in_array(false, $this->selected, true);
        $this->selected = array_fill(0, count($this->proposals), !$allSelected);
    }

    public function commitSelected(): void
    {
        $matches = [];
        foreach ($this->proposals as $i => $proposal) {
            if ($this->selected[$i] ?? false) {
                $matches[$proposal['graduate_id']] = $proposal['student_number'];
            }
        }

        if (empty($matches)) {
            Notification::make()
                ->title('No matches selected')
                ->warning()
                ->duration(3000)
                ->send();
            return;
        }

        $count = GraduateMatchService::commitMatches($matches);

        Notification::make()
            ->title("$count matches committed")
            ->success()
            ->duration(5000)
            ->send();

        $this->proposals = [];
        $this->selected = [];
        $this->unmountAction('reviewMatches');
    }

    public function cancelReview(): void
    {
        $this->proposals = [];
        $this->selected = [];
        $this->unmountAction('reviewMatches');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Graduate::query())
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Student #')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => $state ? Enrollee::formatStudentNumber($state) : '—')
                    ->url(fn (Graduate $record): ?string => $record->student_number
                        ? '/admin/list-of-students?studentNumber=' . urlencode($record->student_number)
                        : null)
                    ->color(fn (Graduate $record): ?string => $record->student_number ? 'primary' : null),
                Tables\Columns\TextColumn::make('match_type')
                    ->label('Match')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'auto' => 'info',
                        'manual' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'auto' => 'Auto',
                        'manual' => 'Manual',
                        default => '—',
                    }),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'imported' => 'primary',
                        'manual' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'imported' => 'GS Import',
                        'manual' => 'Manual',
                        default => '—',
                    }),
                Tables\Columns\TextColumn::make('degree')
                    ->label('Degree')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('program_name')
                    ->label('Program')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('major')
                    ->label('Specialization')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('major_field_raw')
                    ->label('Major/Field (Raw)')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('semester_graduated')
                    ->label('Semester')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '—';
                        if (is_numeric($state)) {
                            $sem = \App\Models\Semester::where('term_code', $state)->first();
                            return $sem ? $sem->short_label : $state;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('semester_raw')
                    ->label('Raw Semester')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('country_of_origin')
                    ->label('Country')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('chair')
                    ->label('Chair')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('co_chair')
                    ->label('Co-Chair')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('degree')
                    ->label('Degree')
                    ->options(fn () => Graduate::distinct()->whereNotNull('degree')->where('degree', '!=', '')->pluck('degree', 'degree')->toArray())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('program_name')
                    ->label('Program')
                    ->options(fn () => Graduate::distinct()->whereNotNull('program_name')->where('program_name', '!=', '')->pluck('program_name', 'program_name')->toArray())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('major')
                    ->label('Specialization')
                    ->options(fn () => Graduate::distinct()->whereNotNull('major')->where('major', '!=', '')->pluck('major', 'major')->toArray())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('semester_graduated')
                    ->label('Semester')
                    ->multiple()
                    ->options(function () {
                        $semesters = Graduate::distinct()
                            ->whereNotNull('semester_graduated')
                            ->pluck('semester_graduated')
                            ->filter();
                        $result = [];
                        foreach ($semesters as $code) {
                            if (is_numeric($code)) {
                                $sem = \App\Models\Semester::where('term_code', $code)->first();
                                $result[$code] = $sem ? $sem->short_label : $code;
                            } else {
                                $result[$code] = $code;
                            }
                        }
                        return $result;
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('match_type')
                    ->label('Match Type')
                    ->options([
                        'auto' => 'Auto',
                        'manual' => 'Manual',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'imported' => 'GS Import',
                        'manual' => 'Manual',
                    ]),
                Tables\Filters\TernaryFilter::make('matched')
                    ->label('Matched?')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('student_number')->where('student_number', '!=', ''),
                        false: fn (Builder $q) => $q->where(fn ($q2) => $q2->whereNull('student_number')->orWhere('student_number', '')),
                    ),
                Tables\Filters\SelectFilter::make('country_of_origin')
                    ->label('Country')
                    ->options(fn () => Graduate::distinct()->whereNotNull('country_of_origin')->where('country_of_origin', '!=', '')->pluck('country_of_origin', 'country_of_origin')->toArray())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('assignStudentNumber')
                    ->label('Assign')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->schema([
                        Forms\Components\TextInput::make('student_number')
                            ->label('Student Number')
                            ->required()
                            ->placeholder('e.g. 202012345'),
                    ])
                    ->action(function (Graduate $record, array $data): void {
                        $record->update([
                            'student_number' => $data['student_number'],
                            'match_type' => 'manual',
                        ]);
                        Notification::make()
                            ->title('Student number assigned')
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),
                Action::make('findMatch')
                    ->label('Find')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->schema(function (Graduate $record): array {
                        $candidates = GraduateMatchService::findCandidates($record, 5, 60);
                        $options = [];
                        foreach ($candidates as $c) {
                            $options[$c['student_number']] = "{$c['full_name']} ({$c['degree_program']}) — {$c['similarity']}%";
                        }
                        return [
                            Forms\Components\Radio::make('student_number')
                                ->label('Select a match')
                                ->options($options)
                                ->required()
                                ->descriptions(
                                    collect($candidates)->mapWithKeys(fn ($c) => [
                                        $c['student_number'] => 'Student #: ' . Enrollee::formatStudentNumber($c['student_number']) . ' | Term: ' . $c['term_id'],
                                    ])->toArray()
                                ),
                        ];
                    })
                    ->action(function (Graduate $record, array $data): void {
                        $record->update([
                            'student_number' => $data['student_number'],
                            'match_type' => 'manual',
                        ]);
                        Notification::make()
                            ->title('Match assigned')
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),
                Action::make('unmatch')
                    ->label('Unmatch')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will remove the student number link from this graduate record.')
                    ->visible(fn (Graduate $record): bool => !empty($record->student_number))
                    ->action(function (Graduate $record): void {
                        $record->update([
                            'student_number' => null,
                            'match_type' => null,
                        ]);
                        Notification::make()
                            ->title('Match removed')
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
