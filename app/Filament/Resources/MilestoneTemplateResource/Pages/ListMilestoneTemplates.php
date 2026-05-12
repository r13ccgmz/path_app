<?php

namespace App\Filament\Resources\MilestoneTemplateResource\Pages;

use App\Filament\Resources\MilestoneTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMilestoneTemplates extends ListRecords
{
    protected static string $resource = MilestoneTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Milestone Template')
                ->icon('heroicon-o-plus'),
        ];
    }
}
