<?php

namespace App\Filament\Pages;

use App\Models\Enrollee;
use App\Models\Graduate;
use App\Services\GraduateMatchService;
use App\Filament\Widgets\MatchReportStats;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MatchReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \UnitEnum | null $navigationGroup = 'Student Management';
    protected static ?string $navigationLabel = 'Match Report';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Graduate ↔ Enrollee Match Report';
    protected static ?int $navigationSort = 4;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $slug = 'match-report';
    protected string $view = 'filament.pages.match-report';

    public bool $showProposals = false;
    public array $proposals = [];
    public array $selected = [];

    protected function getHeaderWidgets(): array
    {
        return [
            MatchReportStats::class,
        ];
    }

    public function scanForMatches(): void
    {
        $this->proposals = GraduateMatchService::scanAll(80);
        $this->selected = array_fill(0, count($this->proposals), true);
        $this->showProposals = true;

        if (empty($this->proposals)) {
            Notification::make()
                ->title('No matches found')
                ->body('All graduates are either already matched or no candidates above the 80% threshold were found.')
                ->warning()
                ->duration(5000)
                ->send();
            $this->showProposals = false;
        }
    }

    public function toggleAll(): void
    {
        $allSelected = !in_array(false, $this->selected, true);
        $this->selected = array_fill(0, count($this->proposals), !$allSelected);
    }

    public function cancelScan(): void
    {
        $this->showProposals = false;
        $this->proposals = [];
        $this->selected = [];
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

        $this->showProposals = false;
        $this->proposals = [];
        $this->selected = [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Graduate::query()
                    ->whereNotNull('student_number')
                    ->where('student_number', '!=', '')
            )
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Graduate Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Student #')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => Enrollee::formatStudentNumber($state))
                    ->url(fn (Graduate $record): string =>
                        '/admin/list-of-students?studentNumber=' . urlencode($record->student_number)
                    )
                    ->color('primary'),
                Tables\Columns\TextColumn::make('match_type')
                    ->label('Match Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'auto' => 'info',
                        'manual' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '—')),
                Tables\Columns\TextColumn::make('degree')
                    ->label('Degree')
                    ->sortable(),
                Tables\Columns\TextColumn::make('program_name')
                    ->label('Program')
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('semester_graduated')
                    ->label('Semester')
                    ->sortable()
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '—';
                        if (is_numeric($state)) {
                            $sem = \App\Models\Semester::where('term_code', $state)->first();
                            return $sem ? $sem->short_label : $state;
                        }
                        return $state;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('match_type')
                    ->label('Match Type')
                    ->options([
                        'auto' => 'Auto',
                        'manual' => 'Manual',
                    ]),
            ])
            ->recordActions([
                Action::make('removeMatch')
                    ->label('Unmatch')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
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
