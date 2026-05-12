<?php

namespace App\Filament\Resources\CognateFields\Pages;

use App\Filament\Resources\CognateFields\CognateFieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCognateFields extends ListRecords
{
    protected static string $resource = CognateFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
