<?php

namespace App\Filament\Resources;

use App\Models\MilestoneTemplate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class MilestoneTemplateResource extends Resource
{
    protected static ?string $model = MilestoneTemplate::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Milestone Templates';

    protected static ?string $pluralModelLabel = 'Milestone Templates';

    /**
     * Shared form schema used by both the resource form and modal create/edit actions.
     */
    public static function getModalFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Milestone Details')
                ->icon('heroicon-o-flag')
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Milestone Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Comprehensive Examination Passed')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Brief description of this milestone...')
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('category')
                        ->label('Category')
                        ->options([
                            'coursework' => 'Coursework',
                            'examination' => 'Examination',
                            'research' => 'Research',
                            'publication' => 'Publication',
                            'defense' => 'Defense',
                            'other' => 'Other',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->required()
                        ->default(fn () => (MilestoneTemplate::max('sort_order') ?? 0) + 1)
                        ->minValue(1)
                        ->live()
                        ->rules([
                            'integer',
                            'min:1',
                            fn (callable $get, ?MilestoneTemplate $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                if ($get('resolve_conflict')) {
                                    return;
                                }

                                $query = MilestoneTemplate::where('sort_order', $value);
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }
                                if ($query->exists()) {
                                    $fail('This sort order is already in use. Select "Shift conflicting templates" to resolve this.');
                                }
                            }
                        ])
                        ->helperText('Lower numbers appear first — must be unique'),
                    Forms\Components\Checkbox::make('resolve_conflict')
                        ->label('Shift conflicting milestone templates')
                        ->helperText('If checked, templates with this or higher sort orders will be shifted down.')
                        ->default(true)
                        ->visible(fn (callable $get, ?MilestoneTemplate $record): bool => 
                            $get('sort_order') !== null && 
                            $get('sort_order') > 0 &&
                            MilestoneTemplate::where('sort_order', $get('sort_order'))
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->exists()
                        )
                        ->live()
                        ->columnSpanFull(),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Applicability')
                ->icon('heroicon-o-academic-cap')
                ->description('Define which programs and degree levels this milestone applies to.')
                ->collapsible()
                ->schema([
                    Forms\Components\Toggle::make('applies_to_all_programs')
                        ->label('Applies to All Programs')
                        ->default(false)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('program_id', null);
                            }
                        })
                        ->helperText('Enable to apply this milestone to every program'),
                    Forms\Components\Toggle::make('is_required')
                        ->label('Required for Graduation')
                        ->default(true)
                        ->helperText('Required milestones must be completed before graduation'),
                    Forms\Components\Select::make('program_id')
                        ->label('Specific Program')
                        ->relationship('program', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->visible(fn (callable $get): bool => !$get('applies_to_all_programs'))
                        ->helperText('Leave empty if this applies to all programs'),
                    Forms\Components\Select::make('degree_level')
                        ->label('Degree Level')
                        ->options([
                            'masters' => "Master's",
                            'doctorate' => 'Doctorate',
                        ])
                        ->nullable()
                        ->placeholder('All levels')
                        ->helperText('Leave empty for all degree levels'),
                ])->columns(2),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getModalFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->description(fn (MilestoneTemplate $record): ?string => $record->description),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'coursework' => 'primary',
                        'examination' => 'warning',
                        'research' => 'info',
                        'publication' => 'success',
                        'defense' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'coursework' => 'Coursework',
                        'examination' => 'Examination',
                        'research' => 'Research',
                        'publication' => 'Publication',
                        'defense' => 'Defense',
                        'other' => 'Other',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('program.code')
                    ->label('Program')
                    ->default('All Programs')
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'All Programs' ? 'gray' : 'primary'),
                Tables\Columns\TextColumn::make('degree_level')
                    ->label('Degree')
                    ->default('all')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'masters' => "Master's",
                        'doctorate' => 'Doctorate',
                        default => 'All Levels',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'masters' => 'info',
                        'doctorate' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'coursework' => 'Coursework',
                        'examination' => 'Examination',
                        'research' => 'Research',
                        'publication' => 'Publication',
                        'defense' => 'Defense',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('degree_level')
                    ->label('Degree Level')
                    ->options([
                        'masters' => "Master's",
                        'doctorate' => 'Doctorate',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn (MilestoneTemplate $record) => "Edit: {$record->name}")
                    ->modalWidth('3xl')
                    ->form(static::getModalFormSchema())
                    ->using(function (MilestoneTemplate $record, array $data): MilestoneTemplate {
                        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            if (!empty($data['resolve_conflict']) && !empty($data['sort_order'])) {
                                static::shiftSortOrders((int)$data['sort_order'], $record->sort_order);
                            }
                            unset($data['resolve_conflict']);
                            $record->update($data);
                            return $record;
                        });
                    })
                    ->successNotificationTitle('Milestone template updated')
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
                DeleteAction::make()
                    ->successNotificationTitle('Milestone template deleted')
                    ->visible(fn () => !auth()->user()->hasRole('viewer')),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function shiftSortOrders(int $newSortOrder, ?int $oldSortOrder = null): void
    {
        if ($oldSortOrder === null) {
            // Creation: Shift all templates with sort_order >= $newSortOrder up by 1
            $templatesToShift = MilestoneTemplate::where('sort_order', '>=', $newSortOrder)
                ->orderBy('sort_order', 'desc')
                ->get();

            foreach ($templatesToShift as $template) {
                $template->sort_order += 1;
                $template->save();
            }
        } else {
            // Editing:
            if ($newSortOrder < $oldSortOrder) {
                // Moving down/earlier: Shift templates in [new, old - 1] up by 1
                $templatesToShift = MilestoneTemplate::whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])
                    ->orderBy('sort_order', 'desc')
                    ->get();

                foreach ($templatesToShift as $template) {
                    $template->sort_order += 1;
                    $template->save();
                }
            } elseif ($newSortOrder > $oldSortOrder) {
                // Moving up/later: Shift templates in [old + 1, new] down by 1
                $templatesToShift = MilestoneTemplate::whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])
                    ->orderBy('sort_order', 'asc')
                    ->get();

                foreach ($templatesToShift as $template) {
                    $template->sort_order -= 1;
                    $template->save();
                }
            }
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\MilestoneTemplateResource\Pages\ListMilestoneTemplates::route('/'),
        ];
    }
}
