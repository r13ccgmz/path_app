<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListAcademicYears extends ListRecords
{
    protected static string $resource = AcademicYearResource::class;

    public ?string $activeTab = 'academic_years';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getView(): string
    {
        return 'filament.resources.academic-years.pages.list-academic-years';
    }

    public function getAcademicYearsCount(): int
    {
        return \App\Models\AcademicYear::count();
    }

    public function getTermCodesCount(): int
    {
        return \App\Models\Semester::count();
    }

    #[On('refreshAcademicYearsPage')]
    public function refreshCounts(): void
    {
        // This method will trigger Livewire to re-render the page, updating the badges.
    }
}
