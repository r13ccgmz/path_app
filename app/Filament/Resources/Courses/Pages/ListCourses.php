<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\CognateFields\CognateFieldResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

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
                ->url(CognateFieldResource::getUrl()),
            CreateAction::make(),
        ];
    }
}
