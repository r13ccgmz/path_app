<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\CognateFields\CognateFieldResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use App\Models\CognateField;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_cognates')
                ->label('Manage Cognate Fields')
                ->icon('heroicon-o-tag')
                ->color('gray')
                ->modalWidth('4xl')
                ->fillForm(fn () => [
                    'cognates' => CognateField::orderBy('name')
                        ->get(['id', 'name', 'description'])
                        ->toArray(),
                ])
                ->form([
                    Repeater::make('cognates')
                        ->hiddenLabel()
                        ->addActionLabel('Add Cognate Field')
                        ->schema([
                            Hidden::make('id'),
                            TextInput::make('name')
                                ->label('Field Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Strategic Planning and Policy Studies'),
                            TextInput::make('description')
                                ->label('Description')
                                ->placeholder('Optional description'),
                        ])
                        ->columns(2)
                        ->default([]),
                ])
                ->action(function (array $data) {
                    $submittedCognates = $data['cognates'] ?? [];
                    $submittedIds = collect($submittedCognates)->pluck('id')->filter()->toArray();

                    // Delete cognates that are no longer in the list
                    CognateField::whereNotIn('id', $submittedIds)->delete();

                    // Update existing or create new
                    foreach ($submittedCognates as $item) {
                        if (!empty($item['id'])) {
                            CognateField::where('id', $item['id'])->update([
                                'name' => $item['name'],
                                'description' => $item['description'] ?? null,
                            ]);
                        } else {
                            CognateField::create([
                                'name' => $item['name'],
                                'description' => $item['description'] ?? null,
                            ]);
                        }
                    }

                    Notification::make()
                        ->title('Cognate fields updated successfully')
                        ->success()
                        ->send();
                })
                ->visible(fn () => !auth()->user()->hasRole('viewer')),
            CreateAction::make()
                ->modalHeading('Create Course')
                ->modalWidth('4xl')
                ->visible(fn () => !auth()->user()->hasRole('viewer')),
        ];
    }
}
