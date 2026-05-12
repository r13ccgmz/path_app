<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-create academic years up to the current calendar year
        try {
            if (Schema::hasTable('academic_years')) {
                $currentYear = (int) date('Y');
                $exists = DB::table('academic_years')
                    ->where('year_start', $currentYear)
                    ->exists();

                if (! $exists) {
                    DB::table('academic_years')->updateOrInsert(
                        ['year_start' => $currentYear],
                        [
                            'year_end' => $currentYear + 1,
                            'is_current' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            // Silently skip — DB may not be available during artisan commands
        }
    }
}

