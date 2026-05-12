<?php

namespace App\Filament\Resources\CognateFields\Pages;

use App\Filament\Resources\CognateFields\CognateFieldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCognateField extends EditRecord
{
    protected static string $resource = CognateFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
