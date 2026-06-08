<x-filament-widgets::widget>
    <x-filament::section class="fi-section-term-enrollee">
        <x-slot name="heading">
            <div class="flex flex-col">
                <span class="text-base font-bold tracking-tight text-gray-900 dark:text-white">Term Enrollment Focus</span>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Detailed breakdown of unique enrollees and course registrations.</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Enrollee Count Card --}}
                <div class="rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5 flex flex-col justify-center bg-gray-50 dark:bg-gray-800/50">
                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unique Enrollees</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">
                        {{ number_format($this->getEnrolleeCount()) }}
                    </div>
                </div>

                {{-- Course Registrations Card --}}
                <div class="rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-5 flex flex-col justify-center bg-primary-50 dark:bg-primary-900/20">
                    <div class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider">Course Registrations</div>
                    <div class="text-3xl font-bold text-primary-700 dark:text-primary-300 mt-1" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">
                        {{ number_format($this->getCourseRegistrationsCount()) }}
                    </div>
                </div>
            </div>

            {{-- Program Breakdown --}}
            @php $programs = $this->getProgramBreakdown(); @endphp
            @if (count($programs) > 0)
                <div class="mt-4">
                    <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wider pl-1">Program Breakdown</h4>
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10 shadow-sm">
                        <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-white/5">
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
                                @foreach ($programs as $program)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {{ $program['program'] }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white whitespace-nowrap w-24" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">
                                            {{ number_format($program['count']) }}
                                        </td>
                                        <td class="px-4 py-2 w-28 text-right">
                                            <x-filament::button wire:click="exportProgram('{{ addslashes($program['program']) }}')" icon="heroicon-m-arrow-down-tray" size="xs" color="success">
                                                Export
                                            </x-filament::button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
