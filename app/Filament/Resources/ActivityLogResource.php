<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 7;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Activity Log';
    protected static ?string $modelLabel = 'Activity Log';
    protected static ?string $pluralModelLabel = 'Activity Logs';
    protected static ?string $slug = 'activity-log';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->description(fn (Activity $record): string => $record->created_at->diffForHumans()),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Who')
                    ->default('System')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph('causer', [\App\Models\User::class], fn ($q) => $q
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                        );
                    })
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'unknown')),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Record Type')
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) return '—';
                        return class_basename($state);
                    })
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Record ID')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Changes')
                    ->formatStateUsing(function (Activity $record): string {
                        $props = $record->properties;
                        if ($props->isEmpty()) return '—';
                        $old = $props->get('old', []);
                        $new = $props->get('attributes', []);

                        // For delete events, show key identifiers from old values
                        if ($record->event === 'deleted' && !empty($old)) {
                            $identifiers = array_filter([
                                $old['full_name'] ?? $old['name'] ?? null,
                                $old['student_number'] ?? $old['code'] ?? $old['course_code'] ?? null,
                                $old['email'] ?? null,
                            ]);
                            if (!empty($identifiers)) {
                                return 'Deleted: ' . implode(' | ', $identifiers);
                            }
                            return count($old) . ' field(s) recorded';
                        }

                        // For create events, show key identifiers from new values
                        if ($record->event === 'created' && !empty($new)) {
                            $identifiers = array_filter([
                                $new['full_name'] ?? $new['name'] ?? null,
                                $new['student_number'] ?? $new['code'] ?? $new['course_code'] ?? null,
                            ]);
                            if (!empty($identifiers)) {
                                return 'Created: ' . implode(' | ', $identifiers);
                            }
                            return count($new) . ' field(s) set';
                        }

                        if (empty($old) && empty($new)) return '—';

                        $formatValue = function ($val) {
                            if (is_array($val)) {
                                return json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                            }
                            if (is_bool($val)) {
                                return $val ? 'true' : 'false';
                            }
                            return (string) $val;
                        };

                        $changes = [];
                        foreach ($new as $key => $value) {
                            $oldVal = $old[$key] ?? '—';
                            $changes[] = "{$key}: " . e($formatValue($oldVal)) . " → " . e($formatValue($value));
                        }
                        return implode(', ', array_slice($changes, 0, 3)) . (count($changes) > 3 ? '...' : '');
                    })
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Record Type')
                    ->options(function () {
                        return Activity::query()
                            ->whereNotNull('subject_type')
                            ->distinct()
                            ->pluck('subject_type')
                            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(fn () => \App\Models\User::pluck('email', 'id')->toArray())
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Activity $record): string => 'Activity: ' . ucfirst($record->description))
                    ->modalContent(fn (Activity $record) => view('filament.resources.activity-log-view', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver(),
                Action::make('revert')
                    ->label('Revert')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Revert This Change')
                    ->modalDescription('This will restore the record to its previous values. A new activity log entry will be created for this reversal.')
                    ->visible(fn (Activity $record): bool =>
                        $record->event === 'updated'
                        && $record->subject !== null
                        && !empty($record->properties->get('old', []))
                    )
                    ->action(function (Activity $record) {
                        $subject = $record->subject;
                        if (!$subject) {
                            Notification::make()
                                ->title('Cannot Revert')
                                ->body('The original record no longer exists.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $oldValues = $record->properties->get('old', []);
                        if (empty($oldValues)) {
                            Notification::make()
                                ->title('Cannot Revert')
                                ->body('No previous values found for this change.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Filter out non-fillable attributes
                        $fillable = $subject->getFillable();
                        $valuesToRestore = array_intersect_key($oldValues, array_flip($fillable));

                        if (empty($valuesToRestore)) {
                            Notification::make()
                                ->title('Nothing to Revert')
                                ->body('The changed attributes are not restorable.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $subject->update($valuesToRestore);

                        Notification::make()
                            ->title('Change Reverted')
                            ->body('The record has been restored to its previous values. Check the activity log for the reversal entry.')
                            ->success()
                            ->send();
                    }),
                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restore Deleted Record')
                    ->modalDescription('This will restore the soft-deleted record back to its original state.')
                    ->visible(function (Activity $record): bool {
                        if ($record->event !== 'deleted' || !$record->subject_type || !$record->subject_id) {
                            return false;
                        }
                        // Check if the model uses SoftDeletes and the record is actually trashed
                        $modelClass = $record->subject_type;
                        if (!class_exists($modelClass)) return false;
                        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass));
                        if (!$usesSoftDeletes) return false;
                        $model = $modelClass::withTrashed()->find($record->subject_id);
                        return $model && method_exists($model, 'trashed') && $model->trashed();
                    })
                    ->action(function (Activity $record) {
                        $modelClass = $record->subject_type;
                        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass));
                        if (!$usesSoftDeletes) {
                            Notification::make()
                                ->title('Cannot Restore')
                                ->body('The record could not be found or does not support restoration.')
                                ->danger()
                                ->send();
                            return;
                        }
                        $model = $modelClass::withTrashed()->find($record->subject_id);
                        if ($model && method_exists($model, 'restore')) {
                            $model->restore();
                            Notification::make()
                                ->title('Record Restored')
                                ->body('The ' . class_basename($record->subject_type) . ' record has been restored successfully.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Cannot Restore')
                                ->body('The record could not be found or does not support restoration.')
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('forceDelete')
                    ->label('Permanently Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Permanently Delete Record')
                    ->modalDescription('⚠️ This action is IRREVERSIBLE. The record will be permanently removed from the database and cannot be recovered.')
                    ->visible(function (Activity $record): bool {
                        if (!$record->subject_type || !$record->subject_id) return false;
                        $modelClass = $record->subject_type;
                        if (!class_exists($modelClass)) return false;
                        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass));
                        if (!$usesSoftDeletes) return false;
                        // Show for soft-deleted records
                        if (!method_exists(new $modelClass, 'trashed')) return false;
                        $model = $modelClass::withTrashed()->find($record->subject_id);
                        return $model && $model->trashed();
                    })
                    ->action(function (Activity $record) {
                        $modelClass = $record->subject_type;
                        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass));
                        if (!$usesSoftDeletes) {
                            Notification::make()
                                ->title('Record Not Found')
                                ->body('The record has already been permanently deleted.')
                                ->warning()
                                ->send();
                            return;
                        }
                        $model = $modelClass::withTrashed()->find($record->subject_id);
                        if ($model) {
                            $model->forceDelete();
                            Notification::make()
                                ->title('Permanently Deleted')
                                ->body('The ' . class_basename($record->subject_type) . ' record has been permanently removed.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Record Not Found')
                                ->body('The record has already been permanently deleted.')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('No activity recorded yet')
            ->emptyStateDescription('Changes to records will appear here automatically once activity logging is active.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ActivityLogResource\Pages\ListActivityLogs::route('/'),
        ];
    }
}
