<?php

namespace App\Filament\Pages;

use App\Models\Faculty;
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

class MentorshipMonitoring extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\UnitEnum|null $navigationGroup = 'Main';
    protected static ?string $navigationLabel = 'Workload & Mentorship Analytics';
    protected static ?string $title = 'Workload & Mentorship Analytics Dashboard';
    protected static ?int $navigationSort = 5;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    
    protected string $view = 'filament.pages.mentorship-monitoring';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_faculty')
                ->label('New Faculty / External')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('first_name')->required(),
                    Forms\Components\TextInput::make('last_name')->required(),
                    Forms\Components\TextInput::make('designation')->label('Designation / Affiliation'),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\Toggle::make('is_external')
                        ->label('External Member (not part of faculty)')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    Faculty::create([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'designation' => $data['designation'] ?? null,
                        'email' => $data['email'] ?? null,
                        'is_external' => $data['is_external'] ?? false,
                        'is_active' => true,
                        'faculty_status' => 'active',
                    ]);
                    \Filament\Notifications\Notification::make()
                        ->title('Created successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\MentorshipStatsWidget::class,
            \App\Filament\Widgets\AdviseeDistributionChart::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Faculty::query()->where('is_external', false))
            ->modifyQueryUsing(function (Builder $query) {
                $termCodes = $this->getTableFilterState('term')['values'] ?? [];

                $query->withCount([
                    'advisees as masters_advisees_count' => function (Builder $q) use ($termCodes) {
                        $q->whereHas('program', function ($p) {
                            $p->whereIn('degree_level', ['master', 'master_of_science']);
                        });
                        if (!empty($termCodes)) {
                            $q->whereExists(function ($sub) use ($termCodes) {
                                $sub->select(DB::raw(1))
                                    ->from('enrollees')
                                    ->whereColumn('enrollees.student_number', 'students.student_number')
                                    ->whereIn('enrollees.term_id', $termCodes);
                            });
                        }
                    },
                    'advisees as phd_advisees_count' => function (Builder $q) use ($termCodes) {
                        $q->whereHas('program', function ($p) {
                            $p->where('degree_level', 'doctorate');
                        });
                        if (!empty($termCodes)) {
                            $q->whereExists(function ($sub) use ($termCodes) {
                                $sub->select(DB::raw(1))
                                    ->from('enrollees')
                                    ->whereColumn('enrollees.student_number', 'students.student_number')
                                    ->whereIn('enrollees.term_id', $termCodes);
                            });
                        }
                    },
                    'committeeMemberships as chair_count' => function (Builder $q) {
                        $q->whereIn('role', ['chair', 'co-chair']);
                    },
                    'committeeMemberships as member_count' => function (Builder $q) {
                        $q->whereIn('role', ['member', 'panel']);
                    },
                ]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Faculty Name')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(['last_name']),
                Tables\Columns\TextColumn::make('designation')
                    ->label('Designation')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('masters_advisees_count')
                    ->label("Master's Advisees")
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(function ($state, $livewire) {
                        $limit = $livewire->getTableFilterState('overload_limit')['limit'] ?? 5;
                        return $state >= $limit ? 'danger' : 'info';
                    }),
                Tables\Columns\TextColumn::make('phd_advisees_count')
                    ->label('PhD Advisees')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(function ($state, $livewire) {
                        $limit = $livewire->getTableFilterState('overload_limit')['limit'] ?? 5;
                        return $state >= $limit ? 'danger' : 'success';
                    }),
                Tables\Columns\TextColumn::make('chair_count')
                    ->label('Chair / Co-Chair')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('member_count')
                    ->label('Member / Panel')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('last_name')
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
                Tables\Filters\SelectFilter::make('term')
                    ->label('Semester / Term Workload')
                    ->multiple()
                    ->options(function () {
                        return \App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
                            ->toArray();
                    })
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query),
                Tables\Filters\Filter::make('overload_limit')
                    ->form([
                        Forms\Components\TextInput::make('limit')
                            ->label('Overload Threshold')
                            ->numeric()
                            ->default(5)
                            ->hint('Adjusts the danger badge limit')
                    ])
                    ->query(function (Builder $query) {
                        return $query;
                    }),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('first_name')->required(),
                        Forms\Components\TextInput::make('last_name')->required(),
                        Forms\Components\TextInput::make('designation')->label('Designation / Affiliation'),
                        Forms\Components\Select::make('faculty_status')
                            ->label('Faculty Status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->required(),
                    ]),
                \Filament\Actions\Action::make('view_students')
                    ->label('View Assigned Students')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(fn (Faculty $record) => "Mentorship Details: {$record->full_name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('4xl')
                    ->modalContent(function (Faculty $record) {
                        return view('filament.components.faculty-mentorship-details', [
                            'faculty' => $record,
                        ]);
                    })
            ]);
    }

    public string $externalSearch = '';

    /**
     * Provide the external members data to the blade view.
     */
    protected function getViewData(): array
    {
        $query = Faculty::where('is_external', true)->orderBy('last_name');
        
        if (!empty($this->externalSearch)) {
            $search = $this->externalSearch;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return [
            'externalMembers' => $query->get(),
        ];
    }

    /**
     * Edit an external member inline.
     */
    public function editExternal(int $id): void
    {
        $member = Faculty::findOrFail($id);
        $this->mountAction('editExternalAction', ['id' => $id]);
    }

    /**
     * Delete an external member.
     */
    public function deleteExternal(int $id): void
    {
        $member = Faculty::findOrFail($id);
        $member->forceDelete();

        \Filament\Notifications\Notification::make()
            ->title('External member deleted')
            ->success()
            ->send();
    }

    /**
     * Register the editExternalAction as a page-level action.
     */
    public function editExternalAction(): Action
    {
        return Action::make('editExternalAction')
            ->label('Edit External Member')
            ->modalHeading('Edit External Member')
            ->form([
                Forms\Components\TextInput::make('first_name')->required(),
                Forms\Components\TextInput::make('last_name')->required(),
                Forms\Components\TextInput::make('designation')->label('Designation / Affiliation'),
                Forms\Components\TextInput::make('email')->email(),
            ])
            ->fillForm(function (array $arguments): array {
                $member = Faculty::findOrFail($arguments['id']);
                return [
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'designation' => $member->designation,
                    'email' => $member->email,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $member = Faculty::findOrFail($arguments['id']);
                $member->update($data);

                \Filament\Notifications\Notification::make()
                    ->title('External member updated')
                    ->success()
                    ->send();
            });
    }
}
