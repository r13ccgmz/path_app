<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcademicYears extends ListRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('term_codes')
                ->label('Term Codes')
                ->icon(\Filament\Support\Icons\Heroicon::OutlinedHashtag)
                ->url(AcademicYearResource::getUrl('term-codes'))
                ->color('gray'),
            CreateAction::make(),
        ];
    }
}
