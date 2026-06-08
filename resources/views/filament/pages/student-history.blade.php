<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Student Info Card (shown when a student is found) --}}
        @if ($studentInfo)
            {{-- ═══ Student Profile ═══ --}}
            @include('filament.pages.partials._student-profile')

            {{-- ═══ Admission, Advisers & Committee ═══ --}}
            @include('filament.pages.partials._admission-committee')

            {{-- ═══ Graduation Info ═══ --}}
            @include('filament.pages.partials._graduation-info')

            {{-- ═══ Academic Progress Section(s) — one per program ═══ --}}
            @php $allProgress = $this->getAllAcademicProgress(); @endphp

            @if ($allProgress)
                @foreach ($allProgress as $progIdx => $progress)
                    <x-filament::section
                        :heading="($progress['program']->code ?? 'Program') . ' — ' . ($progress['program']->name ?? '') . (!empty($progress['major_name']) ? ' (' . $progress['major_name'] . ')' : '')"
                        icon="heroicon-o-chart-bar" collapsible>

                        @include('filament.pages.partials._academic-progress', ['progress' => $progress])
                    </x-filament::section>
                @endforeach
            @elseif ($this->getStudentRecord())
                {{-- Student exists but no program assigned --}}
                <x-filament::section>
                    <div class="text-center py-6">
                        <x-heroicon-o-academic-cap class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            No program assigned to this student yet.
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            Academic progress tracking requires a program assignment.
                        </p>
                    </div>
                </x-filament::section>
            @endif

            {{-- ═══ Milestones ═══ --}}
            @include('filament.pages.partials._milestones')

            {{-- ═══ Academic Output ═══ --}}
            @include('filament.pages.partials._academic-outputs')

            {{-- ═══ Enrollment History Table ═══ --}}
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                        <x-heroicon-o-academic-cap class="w-6 h-6 text-gray-400" />
                        Enrollment History
                    </h3>
                    @if (auth()->user()->can('Update:Student'))
                    <div>
                        {{ $this->getAction('addProgramEnrollment') }}
                    </div>
                    @endif
                </div>
                {{ $this->table }}
            </div>
        @else
            {{-- Filter Form --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5 shadow-sm mb-6">
                {{ $this->filtersForm }}
            </div>

            {{-- Overview & Demographics Widgets --}}
            <div class="grid grid-cols-1 gap-6 mb-6">
                @livewire(\App\Filament\Widgets\DashboardStudentOverviewWidget::class, ['pageFilters' => $this->filters], key('overview-widget-' . data_get($this->filters, 'filterFrom', 'all') . '-' . data_get($this->filters, 'filterTo', 'all')))
                @livewire(\App\Filament\Widgets\DashboardStudentDemographicsChartsWidget::class, ['pageFilters' => $this->filters], key('demographics-widget-' . data_get($this->filters, 'filterFrom', 'all') . '-' . data_get($this->filters, 'filterTo', 'all')))
            </div>

            {{-- All Students Table (default view) --}}
            @include('filament.pages.partials._student-list-guide')
        @endif
    </div>

</x-filament-panels::page>
