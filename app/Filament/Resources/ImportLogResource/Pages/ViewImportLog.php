<?php

namespace App\Filament\Resources\ImportLogResource\Pages;

use App\Filament\Resources\ImportLogResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewImportLog extends ViewRecord
{
    protected static string $resource = ImportLogResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('downloadResults')
                ->label('Download Results')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => !empty($this->record->results_file) && Storage::disk('local')->exists($this->record->results_file))
                ->action(function () {
                    return Storage::disk('local')->download(
                        $this->record->results_file,
                        'import_results_' . $this->record->created_at->format('Y_m_d_His') . '.xlsx'
                    );
                }),
        ];
    }
}
