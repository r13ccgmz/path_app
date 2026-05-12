<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\SystemSetting;

class Settings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'System Settings';

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.pages.settings';

    // Form state
    public ?int $full_time_units_threshold = 9;

    public function mount(): void
    {
        $this->full_time_units_threshold = (int) SystemSetting::get('full_time_units_threshold', 9);
    }

    public function saveEnrollmentSettings(): void
    {
        $validated = $this->validate([
            'full_time_units_threshold' => 'required|integer|min:1|max:30',
        ]);

        SystemSetting::set('full_time_units_threshold', $validated['full_time_units_threshold']);

        // Clear the SystemSetting cache for this key
        cache()->forget('system_setting:full_time_units_threshold');

        // Clear ALL widget caches — they use dynamic keys with filter suffixes
        // (e.g. student_demographics_data_all_all, dashboard_demographics_data_20231_20242)
        // Since Laravel's file driver doesn't support tag-based or pattern-based clearing,
        // we flush the entire cache. This is acceptable because:
        // 1. This is an admin-only operation that happens rarely
        // 2. Widget caches rebuild quickly (5-minute TTL)
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        Notification::make()
            ->title('Settings Updated')
            ->body("Full-time threshold set to {$validated['full_time_units_threshold']} units. All dashboard caches have been refreshed.")
            ->success()
            ->duration(3000)
            ->send();
    }
}
