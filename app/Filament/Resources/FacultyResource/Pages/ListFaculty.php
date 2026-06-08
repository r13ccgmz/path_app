<?php

namespace App\Filament\Resources\FacultyResource\Pages;

use App\Filament\Resources\FacultyResource;
use App\Models\Faculty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListFaculty extends ListRecords
{
    protected static string $resource = FacultyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Faculty')
                ->icon('heroicon-o-plus')
                ->model(Faculty::class)
                ->modalHeading('Create Faculty Member')
                ->modalWidth('3xl')
                ->form(FacultyResource::getModalFormSchema())
                ->visible(fn () => !auth()->user()->hasRole('viewer')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'faculty' => Tab::make('Faculty')
                ->icon('heroicon-o-user-group')
                ->modifyQueryUsing(fn ($query) => $query->where('is_external', false))
                ->badge(fn () => Faculty::where('is_external', false)->whereNull('deleted_at')->count()),
            'external' => Tab::make('External / Panel Members')
                ->icon('heroicon-o-user-plus')
                ->modifyQueryUsing(fn ($query) => $query->where('is_external', true))
                ->badge(fn () => Faculty::where('is_external', true)->whereNull('deleted_at')->count()),
        ];
    }
}
