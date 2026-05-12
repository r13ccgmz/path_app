<?php

namespace App\Filament\Resources\EnrolleeResource\Pages;

use App\Filament\Resources\EnrolleeResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEnrollees extends ListRecords
{
    protected static string $resource = EnrolleeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\EnrolleeListStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.enrollee-resource.pages.list-enrollees-header');
    }
}
