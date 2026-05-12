<x-filament-panels::page>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Term Code Lookup</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Search for a term code to find its academic year and semester period.</p>
        </div>
        <a href="{{ \App\Filament\Resources\AcademicYears\AcademicYearResource::getUrl('index') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            ← Back to Academic Years
        </a>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
