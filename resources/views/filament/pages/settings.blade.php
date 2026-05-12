<x-filament-panels::page>
    {{-- Enrollment Settings --}}
    <x-filament::section heading="Enrollment Settings" icon="heroicon-o-academic-cap" description="Configure rules for enrollment status classification.">
        <form wire:submit="saveEnrollmentSettings" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="full_time_units_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Full-Time Units Threshold
                    </label>
                    <input type="number" id="full_time_units_threshold" wire:model="full_time_units_threshold"
                        min="1" max="30"
                        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Students enrolled in this many units or more per term will be classified as <strong>Full-Time</strong>. Below this threshold = <strong>Part-Time</strong>.
                    </p>
                </div>
                <div class="flex items-end">
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 w-full">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <x-heroicon-o-information-circle class="w-4 h-4 inline mr-1" />
                            Currently set to <strong>{{ $full_time_units_threshold }} units</strong>.
                            Students with ≥ {{ $full_time_units_threshold }} units/term = Full-Time.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                    Save Enrollment Settings
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    {{-- More settings sections can be added here --}}
    <x-filament::section heading="Other Settings" icon="heroicon-o-wrench-screwdriver" collapsible collapsed>
        <div class="text-center py-8">
            <x-heroicon-o-wrench-screwdriver class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
            <p class="text-sm text-gray-500 dark:text-gray-500 max-w-md mx-auto">
                Additional system configuration options (academic year settings, import configurations, notification preferences) will be available here in a future update.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
