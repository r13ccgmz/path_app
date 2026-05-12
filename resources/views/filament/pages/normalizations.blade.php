<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Helper Guide --}}
        <div class="rounded-xl border border-primary-200 dark:border-primary-800/50 bg-primary-50/50 dark:bg-primary-900/10 px-5 py-4">
            <div class="flex items-start gap-3">
                <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" />
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Normalization Rules</p>
                    <p class="mb-2">Rules applied during import to standardize data from the Graduate School system. Changes apply automatically on the next import.</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li><strong>Program</strong> — Maps abbreviated or inconsistent program names to their full standardized form.</li>
                        <li><strong>Course Code</strong> — Normalizes variant course codes (e.g., RESIDNCE → RESIDENCY).</li>
                    </ul>
                    <p class="mt-2 text-xs"><strong>Tip:</strong> Use <em>Apply to Existing Data</em> to retroactively apply rules to already-imported records. Use <em>Normalize Names</em> to convert ALL CAPS names to Title Case.</p>
                </div>
            </div>
        </div>

        <x-filament::section heading="Normalization Rules" icon="heroicon-o-adjustments-horizontal">
            <x-slot name="description">
                Rules applied during import to normalize program names and course codes. Changes take effect on the next import.
            </x-slot>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
