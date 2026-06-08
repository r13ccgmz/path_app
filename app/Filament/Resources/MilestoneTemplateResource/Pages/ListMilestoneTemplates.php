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
                ->icon('heroicon-o-plus')
                ->modalHeading('Create Milestone Template')
                ->modalWidth('3xl')
                ->form(MilestoneTemplateResource::getModalFormSchema())
                ->using(function (array $data, string $model) {
                    return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $model) {
                        if (!empty($data['resolve_conflict']) && !empty($data['sort_order'])) {
                            MilestoneTemplateResource::shiftSortOrders((int)$data['sort_order']);
                        }
                        unset($data['resolve_conflict']);
                        return $model::create($data);
                    });
                })
                ->successNotificationTitle('Milestone template created')
                ->visible(fn () => !auth()->user()->hasRole('viewer')),
        ];
    }
}
