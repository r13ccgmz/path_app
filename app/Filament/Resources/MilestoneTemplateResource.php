<?php

namespace App\Filament\Resources;

use App\Models\MilestoneTemplate;
use App\Models\Program;
use Filament\Actions\Action;
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

    protected static ?int $navigationSort = 3;


    protected static ?string $navigationLabel = 'Milestone Templates';

    protected static ?string $pluralModelLabel = 'Milestone Templates';

    public static function schema(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Milestone Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Comprehensive Examination Passed')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
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
                            ->required(),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Applicability')
                    ->schema([
                        Forms\Components\Toggle::make('applies_to_all_programs')
                            ->label('Applies to All Programs')
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('program_id', null);
                                }
                            }),
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
                        Forms\Components\Toggle::make('is_required')
                            ->label('Required for Graduation')
                            ->default(true),
                    ])->columns(2),
            ]);
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
                    ->wrap(),
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
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'masters' => "Master's",
                        'doctorate' => 'Doctorate',
                        default => 'All',
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\MilestoneTemplateResource\Pages\ListMilestoneTemplates::route('/'),
            'create' => \App\Filament\Resources\MilestoneTemplateResource\Pages\CreateMilestoneTemplate::route('/create'),
            'edit' => \App\Filament\Resources\MilestoneTemplateResource\Pages\EditMilestoneTemplate::route('/{record}/edit'),
        ];
    }
}
