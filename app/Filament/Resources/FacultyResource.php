<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacultyResource\Pages;
use App\Models\Faculty;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FacultyResource extends Resource
{
    protected static ?string $model = Faculty::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Faculty';
    protected static ?string $modelLabel = 'Faculty';
    protected static ?string $pluralModelLabel = 'Faculty';
    protected static ?string $slug = 'faculty';

    protected static ?string $recordTitleAttribute = 'last_name';

    protected static int $globalSearchResultsLimit = 10;

    public static function getGloballySearchableAttributes(): array
    {
        return ['last_name', 'first_name', 'email'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->full_name;
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            'Designation' => $record->designation,
            'Unit' => $record->unit?->name,
        ]);
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()->where('is_external', false)->with('unit');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('middle_name')
                            ->label('Middle Initial(s)')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('suffix')
                            ->label('Suffix')
                            ->maxLength(20)
                            ->placeholder('e.g. Jr., III'),
                        Forms\Components\TextInput::make('email')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('birthday')
                            ->label('Birthday'),
                        Forms\Components\Select::make('sex')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ]),
                        Forms\Components\TextInput::make('contact_number')
                            ->label('Contact Number')
                            ->tel()
                            ->maxLength(20),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Employment Information')
                    ->schema([
                        Forms\Components\Select::make('designation')
                            ->label('Designation (Current Position)')
                            ->options([
                                'Instructor 1' => 'Instructor 1',
                                'Instructor 2' => 'Instructor 2',
                                'Instructor 3' => 'Instructor 3',
                                'Instructor 4' => 'Instructor 4',
                                'Instructor 5' => 'Instructor 5',
                                'Instructor 6' => 'Instructor 6',
                                'Instructor 7' => 'Instructor 7',
                                'Teaching Associate' => 'Teaching Associate',
                                'Teaching Fellow' => 'Teaching Fellow',
                                'Assistant Professor 1' => 'Assistant Professor 1',
                                'Assistant Professor 2' => 'Assistant Professor 2',
                                'Assistant Professor 3' => 'Assistant Professor 3',
                                'Assistant Professor 4' => 'Assistant Professor 4',
                                'Assistant Professor 5' => 'Assistant Professor 5',
                                'Assistant Professor 6' => 'Assistant Professor 6',
                                'Assistant Professor 7' => 'Assistant Professor 7',
                                'Associate Professor 1' => 'Associate Professor 1',
                                'Associate Professor 2' => 'Associate Professor 2',
                                'Associate Professor 3' => 'Associate Professor 3',
                                'Associate Professor 4' => 'Associate Professor 4',
                                'Associate Professor 5' => 'Associate Professor 5',
                                'Associate Professor 6' => 'Associate Professor 6',
                                'Associate Professor 7' => 'Associate Professor 7',
                                'Professor 1' => 'Professor 1',
                                'Professor 2' => 'Professor 2',
                                'Professor 3' => 'Professor 3',
                                'Professor 4' => 'Professor 4',
                                'Professor 5' => 'Professor 5',
                                'Professor 6' => 'Professor 6',
                                'Professor 7' => 'Professor 7',
                                'Professor 8' => 'Professor 8',
                                'Professor 9' => 'Professor 9',
                                'Professor 10' => 'Professor 10',
                                'Professor 11' => 'Professor 11',
                                'Professor 12' => 'Professor 12',
                                'University Professor' => 'University Professor',
                            ])
                            ->searchable()
                            ->placeholder('Select designation...')
                            ->allowHtml(false),
                        Forms\Components\Select::make('employment_status')
                            ->label('Employment Status')
                            ->options([
                                'full-time' => 'Full-Time',
                                'part-time' => 'Part-Time',
                                'temporary' => 'Temporary',
                                'others' => 'Others',
                            ]),
                        Forms\Components\Select::make('staff_classification')
                            ->label('Staff Classification')
                            ->options([
                                'admin' => 'Admin',
                                'reps' => 'REPS',
                                'faculty' => 'Faculty',
                                'others' => 'Others',
                            ]),
                        Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select unit...'),
                        Forms\Components\DatePicker::make('date_hired_cpaf')
                            ->label('Date Hired (CPAF)'),
                        Forms\Components\Select::make('faculty_status')
                            ->label('Faculty Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Educational Attainment')
                    ->schema([
                        Forms\Components\Select::make('highest_degree')
                            ->label('Highest Educational Attainment')
                            ->options([
                                'high-school' => 'High School',
                                'vocational' => 'Vocational',
                                'bachelors' => "Bachelor's Degree",
                                'masters' => "Master's Degree",
                                'doctorate' => 'Doctorate',
                                'n/a' => 'N/A',
                            ]),
                        Forms\Components\Toggle::make('is_pursuing_postgrad')
                            ->label('Currently Pursuing Postgraduate Degree?')
                            ->live()
                            ->default(false),
                        Forms\Components\TextInput::make('postgrad_program')
                            ->label('Postgraduate Program')
                            ->maxLength(255)
                            ->placeholder('e.g. PhD in Community Development')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_pursuing_postgrad')),
                        Forms\Components\Select::make('postgrad_level')
                            ->label('Postgraduate Level')
                            ->options([
                                'masters' => "Master's",
                                'doctorate' => 'Doctorate',
                            ])
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_pursuing_postgrad')),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Specializations')
                    ->schema([
                        Forms\Components\Select::make('specializations')
                            ->label('Areas of Specialization')
                            ->relationship('specializations', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Specialization Name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('is_external', false))
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->state(fn (Faculty $record): string => $record->full_name)
                    ->sortable(['last_name'])
                    ->searchable(['last_name', 'first_name', 'middle_name']),
                Tables\Columns\TextColumn::make('designation')
                    ->label('Designation')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('highest_degree')
                    ->label('Highest Degree')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'high-school' => 'High School',
                        'vocational' => 'Vocational',
                        'bachelors' => "Bachelor's",
                        'masters' => "Master's",
                        'doctorate' => 'Doctorate',
                        'n/a' => 'N/A',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'doctorate' => 'success',
                        'masters' => 'info',
                        'bachelors' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('employment_status')
                    ->label('Employment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'full-time' => 'Full-Time',
                        'part-time' => 'Part-Time',
                        'temporary' => 'Temporary',
                        'others' => 'Others',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'full-time' => 'success',
                        'part-time' => 'info',
                        'temporary' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff_classification')
                    ->label('Classification')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'reps' => 'REPS',
                        'faculty' => 'Faculty',
                        'others' => 'Others',
                        default => $state ?? '—',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('faculty_status')
                    ->label('Faculty Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_user_account')
                    ->label('Account')
                    ->state(fn (Faculty $record): bool => \App\Models\User::where('faculty_id', $record->id)->exists())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->tooltip(fn (Faculty $record): string => \App\Models\User::where('faculty_id', $record->id)->exists()
                        ? 'Has linked user account'
                        : 'No user account'
                    ),
                Tables\Columns\TextColumn::make('advisees_count')
                    ->label('Advisees')
                    ->counts('advisees')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employment_status')
                    ->label('Employment Status')
                    ->options([
                        'full-time' => 'Full-Time',
                        'part-time' => 'Part-Time',
                        'temporary' => 'Temporary',
                        'others' => 'Others',
                    ]),
                Tables\Filters\SelectFilter::make('highest_degree')
                    ->label('Highest Degree')
                    ->options([
                        'high-school' => 'High School',
                        'vocational' => 'Vocational',
                        'bachelors' => "Bachelor's",
                        'masters' => "Master's",
                        'doctorate' => 'Doctorate',
                        'n/a' => 'N/A',
                    ]),
                Tables\Filters\SelectFilter::make('staff_classification')
                    ->label('Classification')
                    ->options([
                        'admin' => 'Admin',
                        'reps' => 'REPS',
                        'faculty' => 'Faculty',
                        'others' => 'Others',
                    ]),
                Tables\Filters\SelectFilter::make('faculty_status')
                    ->label('Faculty Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('manage_account')
                    ->label(fn (Faculty $record): string =>
                        \App\Models\User::where('faculty_id', $record->id)->exists()
                            ? 'View Account'
                            : 'Create Account'
                    )
                    ->icon(fn (Faculty $record): string =>
                        \App\Models\User::where('faculty_id', $record->id)->exists()
                            ? 'heroicon-o-user'
                            : 'heroicon-o-user-plus'
                    )
                    ->color(fn (Faculty $record): string =>
                        \App\Models\User::where('faculty_id', $record->id)->exists()
                            ? 'info'
                            : 'warning'
                    )
                    ->modalHeading(fn (Faculty $record): string =>
                        \App\Models\User::where('faculty_id', $record->id)->exists()
                            ? "User Account: {$record->full_name}"
                            : "Create User Account for {$record->full_name}"
                    )
                    ->modalWidth('lg')
                    ->form(function (Faculty $record): array {
                        $existingUser = \App\Models\User::where('faculty_id', $record->id)->first();

                        if ($existingUser) {
                            // View/edit mode for existing user
                            return [
                                \Filament\Schemas\Components\Section::make('Linked User Account')
                                    ->schema([
                                        Forms\Components\Placeholder::make('user_id_display')
                                            ->label('User ID')
                                            ->content($existingUser->id),
                                        Forms\Components\TextInput::make('first_name')
                                            ->label('First Name')
                                            ->default($existingUser->first_name)
                                            ->required(),
                                        Forms\Components\TextInput::make('last_name')
                                            ->label('Last Name')
                                            ->default($existingUser->last_name)
                                            ->required(),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->default($existingUser->email)
                                            ->required(),
                                        Forms\Components\Select::make('account_status')
                                            ->label('Login Access')
                                            ->options([
                                                'active' => 'Active (Can log in)',
                                                'inactive' => 'Inactive (Blocked)',
                                            ])
                                            ->default($existingUser->account_status)
                                            ->required(),
                                        Forms\Components\Select::make('roles')
                                            ->label('Roles')
                                            ->multiple()
                                            ->options(\Spatie\Permission\Models\Role::pluck('name', 'name')->toArray())
                                            ->default($existingUser->roles->pluck('name')->toArray()),
                                        Forms\Components\TextInput::make('password')
                                            ->label('New Password')
                                            ->password()
                                            ->helperText('Leave blank to keep current password.')
                                            ->dehydrated(fn ($state) => filled($state)),
                                    ])->columns(2),
                            ];
                        }

                        // Create mode — pre-fill from faculty
                        return [
                            \Filament\Schemas\Components\Section::make('New User Account')
                                ->description('A new login account will be created and linked to this faculty record.')
                                ->schema([
                                    Forms\Components\TextInput::make('first_name')
                                        ->label('First Name')
                                        ->default($record->first_name)
                                        ->required(),
                                    Forms\Components\TextInput::make('last_name')
                                        ->label('Last Name')
                                        ->default($record->last_name)
                                        ->required(),
                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->default($record->email)
                                        ->required()
                                        ->unique(table: 'users', column: 'email', ignoreRecord: false),
                                    Forms\Components\TextInput::make('password')
                                        ->label('Password')
                                        ->password()
                                        ->placeholder('Default: 12345678')
                                        ->helperText('Leave blank to use default password (12345678).')
                                        ->minLength(8),
                                    Forms\Components\Select::make('account_status')
                                        ->label('Login Access')
                                        ->options([
                                            'active' => 'Active (Can log in)',
                                            'inactive' => 'Inactive (Blocked)',
                                        ])
                                        ->default('active')
                                        ->required(),
                                    Forms\Components\Select::make('roles')
                                        ->label('Roles')
                                        ->multiple()
                                        ->options(\Spatie\Permission\Models\Role::pluck('name', 'name')->toArray())
                                        ->default(['panel_user']),
                                ])->columns(2),
                        ];
                    })
                    ->action(function (Faculty $record, array $data): void {
                        $existingUser = \App\Models\User::where('faculty_id', $record->id)->first();

                        if ($existingUser) {
                            // Update existing user
                            $updateData = [
                                'first_name' => $data['first_name'],
                                'last_name' => $data['last_name'],
                                'email' => $data['email'],
                                'account_status' => $data['account_status'],
                            ];

                            if (!empty($data['password'])) {
                                $updateData['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
                            }

                            $existingUser->update($updateData);

                            if (isset($data['roles'])) {
                                $existingUser->syncRoles($data['roles']);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('User account updated')
                                ->success()
                                ->send();
                        } else {
                            // Create new user linked to this faculty
                            $user = \App\Models\User::create([
                                'first_name' => $data['first_name'],
                                'last_name' => $data['last_name'],
                                'email' => $data['email'],
                                'password' => \Illuminate\Support\Facades\Hash::make($data['password'] ?: '12345678'),
                                'account_status' => $data['account_status'],
                                'faculty_id' => $record->id,
                            ]);

                            // Also link from faculty side
                            $record->update(['user_id' => $user->id]);

                            if (isset($data['roles'])) {
                                $user->syncRoles($data['roles']);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('User account created and linked')
                                ->success()
                                ->send();
                        }
                    })
                    ->modalSubmitActionLabel(fn (Faculty $record): string =>
                        \App\Models\User::where('faculty_id', $record->id)->exists()
                            ? 'Update Account'
                            : 'Create Account'
                    ),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
            'index' => Pages\ListFaculty::route('/'),
            'create' => Pages\CreateFaculty::route('/create'),
            'edit' => Pages\EditFaculty::route('/{record}/edit'),
        ];
    }
}
