<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasAcademicOutputForm;
use App\Models\AcademicOutput;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class AllAcademicOutputs extends Page implements HasTable
{
    use InteractsWithTable, HasAcademicOutputForm;

    protected static ?int $navigationSort = 1;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string | \UnitEnum | null $navigationGroup = 'Student Management';
    protected static ?string $navigationLabel = 'Academic Outputs';
    protected static ?string $title = 'Academic Outputs';
    protected ?string $heading = 'Academic Outputs';
    protected ?string $subheading = 'Manage and view all academic outputs, including theses, dissertations, and field studies, submitted by students across all programs.';
    protected string $view = 'filament.pages.all-academic-outputs';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AcademicOutputStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addAcademicOutput')
                ->label('Add Academic Output')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Add Academic Output')
                ->modalWidth('3xl')
                ->form($this->getAcademicOutputForm())
                ->action(function (array $data): void {
                    $aoData = collect($data)->only([
                        'student_id', 'semester_id', 'title', 'type', 'type_other_description', 'drive_link', 'status', 
                        'proposal_defense_date', 'proposal_defense_result', 'final_defense_date', 'final_defense_result', 'date_submitted'
                    ])->toArray();

                    // Auto-sync term_code from selected semester
                    if (!empty($aoData['semester_id'])) {
                        $semester = \App\Models\Semester::find($aoData['semester_id']);
                        $aoData['term_code'] = $semester?->term_code;
                    }
                    
                    $ao = AcademicOutput::create($aoData);
                    
                    $this->saveCommitteeMembers($ao, $data);
                    
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
                    $ao->students()->sync($pivotData);

                    Notification::make()
                        ->title('Academic Output Added')
                        ->success()
                        ->duration(3000)
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $columns = $this->getAcademicOutputTable();
        
        // Type column has been moved to getAcademicOutputTable

        $filters = $this->getAcademicOutputFilters();
        $filters[] = Tables\Filters\SelectFilter::make('type')
            ->options([
                'thesis' => 'Thesis',
                'dissertation' => 'Dissertation',
                'field-study' => 'Field Study',
            ]);

        return $table
            ->query(AcademicOutput::query()->with('committeeMembers'))
            ->columns($columns)
            ->filters($filters)
            ->actions($this->getAcademicOutputTableActions())
            ->recordUrl(fn (AcademicOutput $record): string =>
                '/admin/list-of-students?studentNumber=' . urlencode($record->student?->student_number ?? '')
            )
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No academic outputs recorded')
            ->emptyStateDescription('Academic outputs will appear here once added.')
            ->emptyStateIcon('heroicon-o-document-duplicate');
    }
}
