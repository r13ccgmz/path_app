<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Helper Guide --}}
        <div class="rounded-xl border border-primary-200 dark:border-primary-800/50 bg-primary-50/50 dark:bg-primary-900/10 px-5 py-4">
            <div class="flex items-start gap-3">
                <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" />
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Normalization Rules</p>
                    <p class="mb-2">Rules applied during import to standardize data from the Graduate School system. Changes apply automatically on the next import.</p>

                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-3 mb-1">Enrollee Rules</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li><strong>Program</strong> — Maps abbreviated or inconsistent program names to their full standardized form (e.g., "DR OF PHILO IN COMMUNITY DEV" → "Doctor of Philosophy in Community Development").</li>
                        <li><strong>Course Code</strong> — Normalizes variant course codes in the raw text and enrollment course table (e.g., RESIDNCE → RESIDENCY).</li>
                    </ul>

                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-3 mb-1">Graduate Rules</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li><strong>Degree</strong> — Normalizes degree abbreviation variants from CSV imports (e.g., "PH.D." → "PhD", "DMG" → "MDMG"). Use <em>Seed Degree Rules</em> to auto-populate common mappings.</li>
                        <li><strong>Major / Field</strong> — Standardizes raw major field text before program resolution (e.g., "comm dev" → "Community Development").</li>
                    </ul>

                    <p class="mt-3 text-xs"><strong>Tip:</strong> Use <em>Apply to Existing Data</em> to retroactively apply rules to already-imported enrollee and graduate records. The <em>Tools</em> menu contains utilities for name normalization and seeding default degree mappings.</p>
                </div>
            </div>
        </div>

        <x-filament::section heading="Normalization Rules" icon="heroicon-o-adjustments-horizontal">
            <x-slot name="description">
                Rules applied during import to normalize program names, course codes, degree abbreviations, and major fields. Changes take effect on the next import.
            </x-slot>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
