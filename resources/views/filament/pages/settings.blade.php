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

    {{-- Graduation Settings --}}
    <x-filament::section heading="Graduation Settings" icon="heroicon-o-trophy" description="Configure thresholds for identifying graduation candidates.">
        <form wire:submit="saveGraduationSettings" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="graduation_candidate_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Candidate Completion Threshold (%)
                    </label>

                    {{-- Styled slider matching Student Progress filter --}}
                    <div x-data="{
                        value: @entangle('graduation_candidate_threshold'),
                        init() {
                            this.$watch('value', v => {
                                v = parseInt(v);
                                if (v < 50) this.value = 50;
                                if (v > 100) this.value = 100;
                            });
                        }
                    }" class="mt-3 space-y-2">
                        {{-- Tick marks --}}
                        <div class="relative h-4 text-xs text-gray-500 dark:text-gray-400">
                            @foreach([50, 75, 85, 90, 100] as $tick)
                                <span class="absolute top-0 whitespace-nowrap"
                                      style="left: {{ (($tick - 50) / 50) * 100 }}%; transform: {{ $tick === 50 ? 'translateX(0)' : ($tick === 100 ? 'translateX(-100%)' : 'translateX(-50%)') }};">
                                    {{ $tick }}%
                                </span>
                            @endforeach
                        </div>

                        {{-- Slider track with fill --}}
                        <div class="relative w-full h-2 bg-gray-200 rounded-lg dark:bg-gray-700 graduation-slider">
                            <div class="absolute h-2 rounded-lg" style="left: 0; background: linear-gradient(90deg, #16a34a, #15803d);"
                                 x-bind:style="'width: ' + ((value - 50) / 50 * 100) + '%'"></div>
                            <input type="range" min="50" max="100" step="5" x-model="value"
                                   class="absolute w-full h-2 cursor-pointer appearance-none bg-transparent"
                                   style="-webkit-appearance: none; z-index: 20; background: transparent;">
                            <style>
                                .graduation-slider input[type=range] {
                                    -webkit-appearance: none;
                                    appearance: none;
                                    background: transparent;
                                    width: 100%;
                                    height: 100%;
                                }
                                .graduation-slider input[type=range]:focus {
                                    outline: none;
                                }
                                .graduation-slider input[type=range]::-webkit-slider-thumb {
                                    -webkit-appearance: none;
                                    appearance: none;
                                    width: 18px;
                                    height: 18px;
                                    border-radius: 50%;
                                    background: #16a34a;
                                    cursor: pointer;
                                    box-shadow: 0 1px 3px rgba(0,0,0,.3);
                                    transition: background-color 0.15s ease-in-out, transform 0.1s ease;
                                }
                                .graduation-slider input[type=range]::-webkit-slider-thumb:hover {
                                    background: #15803d;
                                    transform: scale(1.1);
                                }
                                .graduation-slider input[type=range]::-moz-range-thumb {
                                    width: 18px;
                                    height: 18px;
                                    border-radius: 50%;
                                    background: #16a34a;
                                    cursor: pointer;
                                    border: none;
                                    box-shadow: 0 1px 3px rgba(0,0,0,.3);
                                    transition: background-color 0.15s ease-in-out, transform 0.1s ease;
                                }
                                .graduation-slider input[type=range]::-moz-range-thumb:hover {
                                    background: #15803d;
                                    transform: scale(1.1);
                                }
                                .graduation-slider input[type=range]::-webkit-slider-runnable-track {
                                    background: transparent;
                                    border: none;
                                }
                                .graduation-slider input[type=range]::-moz-range-track {
                                    background: transparent;
                                    border: none;
                                }
                            </style>
                        </div>

                        {{-- Current value display --}}
                        <div class="flex justify-center items-center">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300" x-text="value + '%'"></span>
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Students who have completed at least this percentage of their required units will be flagged as <strong>candidates for graduation</strong>.
                        Saving will automatically sync all student statuses.
                    </p>
                </div>
                <div class="flex items-end">
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 w-full">
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            <x-heroicon-o-trophy class="w-4 h-4 inline mr-1" />
                            Currently set to <strong>{{ $graduation_candidate_threshold }}%</strong>.
                            Students with ≥ {{ $graduation_candidate_threshold }}% completion will be marked as graduation candidates.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                    Save Graduation Settings
                </x-filament::button>

                <x-filament::button type="button" color="warning" icon="heroicon-o-arrow-path"
                    wire:click="syncCandidateStatuses"
                    wire:confirm="This will update student statuses based on the current graduation threshold. Students meeting the threshold will be marked as candidates. Are you sure?">
                    Sync Candidate Statuses Now
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    {{-- Student Status Sync Settings --}}
    <x-filament::section heading="Student Status Sync" icon="heroicon-o-arrow-path" description="Configure automatic student status updates based on enrollment activity.">
        <form wire:submit="saveStudentSyncSettings" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="student_inactivity_semesters" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Inactivity Threshold (Semesters)
                    </label>
                    <input type="number" id="student_inactivity_semesters" wire:model="student_inactivity_semesters"
                        min="1" max="20"
                        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Number of consecutive semesters with no enrollment before a student is marked inactive.
                    </p>
                </div>
                <div>
                    <label for="student_inactive_target_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Target Status
                    </label>
                    <select id="student_inactive_target_status" wire:model="student_inactive_target_status"
                        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                        <option value="inactive">Inactive</option>
                        <option value="absent-without-official-leave">Absent Without Official Leave (AWOL)</option>
                        <option value="on-leave">On Leave</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        The status to assign when a student exceeds the inactivity threshold.
                    </p>
                </div>
                <div class="flex items-end">
                    <div class="rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 p-4 w-full">
                        <p class="text-sm text-orange-700 dark:text-orange-300">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 inline mr-1" />
                            Students with <strong>active</strong> status who haven't enrolled in the last
                            <strong>{{ $student_inactivity_semesters }}</strong> semester(s) will be set to
                            <strong>{{ ucwords(str_replace('-', ' ', $student_inactive_target_status)) }}</strong>.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Exclude Candidates Toggle --}}
            <div class="flex items-center gap-3 p-4 rounded-lg bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/50">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.live="exclude_candidates_from_inactivity" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-500 peer-checked:bg-emerald-600"></div>
                </label>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Exclude graduation candidates from inactivity sync</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">When enabled, students who meet the graduation candidate threshold will not be marked as inactive, even if they haven't enrolled recently.</p>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                    Save Sync Settings
                </x-filament::button>

                <x-filament::button type="button" color="warning" icon="heroicon-o-arrow-path"
                    wire:click="syncStudentStatuses"
                    wire:confirm="This will update student statuses based on the configured rules. Are you sure?">
                    Sync Student Statuses Now
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
