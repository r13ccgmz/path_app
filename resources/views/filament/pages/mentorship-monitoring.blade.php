<x-filament-panels::page>

    {{-- ═══ Faculty Mentorship Table ═══ --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
            <x-heroicon-o-academic-cap class="w-5 h-5" />
            Faculty Advisory Overview
        </h2>
        {{ $this->table }}
    </div>

    {{-- Required for Filament page-level actions (edit modal) --}}
    <x-filament-actions::modals />

</x-filament-panels::page>
