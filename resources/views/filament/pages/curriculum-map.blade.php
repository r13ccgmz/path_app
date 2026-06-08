<x-filament-panels::page>
    <div class="space-y-6" wire:key="curriculum-map-container-{{ $this->selectedProgramId ?? 'none' }}">

        {{-- ═══ Program Selector (Modern) ═══ --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="flex flex-col gap-4">
                    {{-- Top row: selector + actions --}}
                    <div class="flex items-center gap-3">
                        <div class="flex-1" x-data="{ search: '', open: false }" @click.outside="open = false">
                            <div class="relative">
                                <button @click="open = !open; $nextTick(() => { if(open) $refs.progSearch.focus() })" type="button"
                                    class="program-selector-btn w-full">
                                    @if ($this->getSelectedProgram())
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="program-selector-code">{{ $this->getSelectedProgram()->code }}</span>
                                            <span class="program-selector-name truncate">{{ $this->getSelectedProgram()->name }}</span>
                                            <span class="program-selector-level">{{ $this->getSelectedProgram()->degree_level?->label() ?? '' }}</span>
                                            @if($this->getTotalUnits())
                                                <span class="program-selector-units">{{ $this->getTotalUnits() }}u</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">Select a program...</span>
                                    @endif
                                    <svg class="h-4 w-4 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition class="program-selector-dropdown">
                                    <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                        <input x-ref="progSearch" x-model="search" type="text" placeholder="Search programs..." class="program-selector-search" />
                                    </div>
                                    <div class="max-h-64 overflow-y-auto p-1">
                                        @foreach ($this->getPrograms() as $program)
                                            <button
                                                x-show="search === '' || '{{ strtolower($program->name . ' ' . $program->code) }}'.includes(search.toLowerCase())"
                                                wire:click="$set('selectedProgramId', {{ $program->id }})"
                                                @click="open = false; search = ''"
                                                class="program-selector-option {{ $this->selectedProgramId == $program->id ? 'program-selector-option--active' : '' }}"
                                            >
                                                <span class="program-selector-opt-code">{{ $program->code }}</span>
                                                <span class="program-selector-opt-name">{{ $program->name }}</span>
                                                <span class="program-selector-opt-level">{{ $program->degree_level?->label() ?? '' }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Add Program button (Filament Action modal) --}}
                        <div class="shrink-0">
                            {{ $this->createProgramAction }}
                        </div>

                        @if ($this->getSelectedProgram())
                            {{-- Edit Program Settings --}}
                            <button wire:click="mountAction('editProgramSettings')" class="cm-header-btn" title="Edit Program Settings">
                                <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                                <span>Settings</span>
                            </button>

                            {{-- PDF Export --}}
                            <button wire:click="downloadPdf" wire:loading.attr="disabled" class="export-pdf-btn" title="Export as PDF">
                                <svg wire:loading.remove wire:target="downloadPdf" class="h-4.5 w-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                <svg wire:loading wire:target="downloadPdf" class="h-4.5 w-4.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span>PDF</span>
                            </button>

                            {{-- View Mode Toggle --}}
                            <div class="flex items-center gap-1 rounded-lg p-0.5 view-mode-toggle">
                                <button wire:click="$set('viewMode', 'timeline')" class="view-mode-btn {{ $this->viewMode === 'timeline' ? 'view-mode-btn--active' : '' }}" title="Timeline View">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                                </button>
                                <button wire:click="$set('viewMode', 'table')" class="view-mode-btn {{ $this->viewMode === 'table' ? 'view-mode-btn--active' : '' }}" title="Table View">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M10.875 12h-7.5c-.621 0-1.125.504-1.125 1.125m10.5 0h7.5c.621 0 1.125-.504 1.125-1.125M12 12v1.5" /></svg>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Program Overview — merged stats + requirements ═══ --}}
        @if ($this->selectedProgramId)
            @php
                $typeStats = $this->getTypeStats();
                $requirements = $this->getProgramRequirements();
                $availableCourses = $this->getAvailableCourses();
                $minUnitsMap = $this->getMinUnitsPerType();
                $totalStat = collect($typeStats)->firstWhere('type', '__total__');
                $totalMinUnits = collect($typeStats)->where('type', '!=', '__total__')->sum('min_units');
                $totalRequired = $totalStat['min_units'] ?? null;
            @endphp
            <div class="program-overview-card">
                {{-- Stats Pill Strip --}}
                @if (count($typeStats) > 0)
                    <div class="stats-pill-strip">
                        @foreach ($typeStats as $stat)
                            @if ($stat['type'] === '__total__')
                                @continue
                            @endif
                            <div class="stats-pill" style="--pill-color: {{ $stat['color'] }};"
                                 wire:key="stats-pill-{{ $this->selectedProgramId }}-{{ $stat['type'] }}">
                                <div class="stats-pill-color" style="background-color: {{ $stat['color'] }};"></div>
                                <span class="stats-pill-label">{{ $stat['type'] }} Courses</span>
                                <div class="stats-pill-value">
                                    <span class="stats-pill-min">
                                        @if ($stat['min_units'])
                                            — minimum of <strong>{{ $stat['min_units'] }}</strong> {{ Str::plural('unit', $stat['min_units']) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                        {{-- Total Pill (read-only) --}}
                        <div class="stats-pill stats-pill--total" style="--pill-color: #1A5C38;"
                             wire:key="stats-pill-{{ $this->selectedProgramId }}-total">
                            <div class="stats-pill-color" style="background-color: #1A5C38;"></div>
                            <span class="stats-pill-label">Total Required</span>
                            <div class="stats-pill-value text-white">
                                <span class="stats-pill-min">
                                    @if ($totalRequired || $totalMinUnits)
                                        <strong>({{ $totalRequired ?? $totalMinUnits }} units)</strong>
                                    @else
                                        <span class="stats-pill-unset">(not set)</span>
                                    @endif
                                </span>
                            </div>
                        </div>

                    </div>
                @endif

                {{-- Requirements Row (collapsible, read-only) --}}
                <div x-data="{ expanded: false }" class="overview-requirements">
                    <button @click="expanded = !expanded" class="overview-requirements-toggle">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-4 w-4 opacity-60" />
                            <span>Program Requirements</span>
                            <span class="overview-req-count">{{ $requirements->count() }}</span>
                        </div>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="expanded && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div x-show="expanded" x-collapse x-cloak class="overview-requirements-list">
                        @forelse ($requirements as $idx => $req)
                            <div class="overview-req-item" wire:key="req-item-{{ $req->id }}">
                                <span class="overview-req-badge">{{ $idx + 1 }}</span>
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="flex-1">{{ $req->requirement_text }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500 dark:text-gray-400 py-2 px-3">No requirements defined yet. Click Settings to add.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══ Curriculum Display ═══ --}}
        @if ($this->selectedProgramId)
            @php $curriculum = $this->getCurriculum(); @endphp
            <div wire:key="curriculum-viewport-{{ $this->selectedProgramId }}" x-data="{ search: '', selectedType: 'core' }" @program-switched.window="search = ''; selectedType = 'core'" @curriculum-updated.window="search = ''" class="curriculum-viewport" wire:loading.class="opacity-50 pointer-events-none" wire:target="selectedProgramId,addCourseMapping,removeCourseMapping">
                @if (empty($curriculum))
                <x-filament::section wire:key="curriculum-empty-{{ $this->selectedProgramId }}">
                    <div class="py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-6 w-6 text-gray-400"/>
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">No courses mapped</h3>
                        <p class="mt-1 text-sm text-gray-500">This program has no courses assigned to its curriculum yet.</p>
                        <div class="mt-4">
                            <button
                                wire:click="mountAction('addCourseMapping')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 transition"
                            >
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add Courses to Curriculum
                            </button>
                        </div>
                    </div>
                </x-filament::section>

            @elseif ($this->viewMode === 'table')
                {{-- ═══ TABLE VIEW MODE ═══ --}}
                @php $bySemester = $this->getCurriculumBySemester(); @endphp
                <div wire:key="curriculum-table-{{ $this->selectedProgramId }}" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="curriculum-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Name</th>
                                    <th>Type</th>
                                    <th>Units</th>
                                    <th>Semester</th>
                                    <th>Prerequisite</th>
                                    <th>Notes</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bySemester as $semester => $courses)
                                    <tr class="curriculum-table-group-row">
                                        <td colspan="8">
                                            <span class="curriculum-table-group-label">{{ $semester }}</span>
                                            <span class="curriculum-table-group-count">{{ count($courses) }} {{ Str::plural('course', count($courses)) }}</span>
                                        </td>
                                    </tr>
                                    @foreach ($courses as $course)
                                        <tr class="curriculum-table-row {{ !empty($course['ms_conditional']) ? 'curriculum-table-row--ms' : '' }}">
                                            <td>
                                                <span class="compact-code-pill" style="background-color: {{ $course['type_color'] }}20; color: {{ $course['type_color'] }};">
                                                    {{ $course['code'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $course['name'] }}</div>
                                                @if (!empty($course['ms_conditional']))
                                                    <span class="compact-tag ms-conditional mt-0.5">
                                                        <svg class="h-2.5 w-2.5 opacity-70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                        </svg>
                                                        MS Conditional
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-xs font-medium px-2 py-0.5 rounded-full" style="background-color: {{ $course['type_color'] }}15; color: {{ $course['type_color'] }};">
                                                    {{ $course['type_label'] }}
                                                </span>
                                            </td>
                                            <td class="text-center font-medium">{{ $course['units'] ?? '—' }}</td>
                                            <td class="text-xs text-gray-500 dark:text-gray-400">{{ $course['semester'] ?? '—' }}</td>
                                            <td class="text-xs text-gray-500 dark:text-gray-400">{{ $course['prerequisite'] ?? '—' }}</td>
                                            <td class="text-xs text-gray-500 dark:text-gray-400 italic">{{ $course['notes'] ?? '—' }}</td>
                                            <td class="text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button x-on:click="$wire.set('editingMappingId', {{ $course['mapping_id'] }}); $nextTick(() => $wire.mountAction('editCourseMapping'))" class="table-action-btn" title="Edit">
                                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="removeCourseMapping({{ $course['mapping_id'] }})" wire:confirm="Remove this course from the program curriculum?" class="table-action-btn table-action-btn--danger" title="Remove">
                                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            @else
                {{-- ═══ TIMELINE VIEW MODE ═══ --}}
                <div wire:key="curriculum-timeline-{{ $this->selectedProgramId }}" class="curriculum-timeline">
                    @foreach ($curriculum as $courseType => $specializations)
                        @php
                            $phaseColor = \App\Filament\Pages\CurriculumMap::getTypeColor($courseType);
                            $phaseColorLight = \App\Filament\Pages\CurriculumMap::getTypeColorLight($courseType);
                            $shortLabel = \App\Filament\Pages\CurriculumMap::getTypeShortLabel($courseType);
                            $displayLabel = $courseType;
                            $typeTotal = array_sum(array_map('count', $specializations));
                            $typeMinUnits = $minUnitsMap[$courseType] ?? null;
                        @endphp

                        <div class="timeline-phase">
                            {{-- Timeline Node with Icon --}}
                            <div class="timeline-node" style="background-color: {{ $phaseColor }};">
                                @switch($courseType)
                                    @case('Core')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
                                        @break
                                    @case('Major')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
                                        @break
                                    @case('Specialization')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                                        @break
                                    @case('Prescribed')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" /></svg>
                                        @break
                                    @case('Cognate')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                                        @break
                                    @case('Elective')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z" /></svg>
                                        @break
                                    @case('Seminar')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                                        @break
                                    @case('Thesis')
                                    @case('Dissertation')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a23.54 23.54 0 0 0-2.578 6.236A47.624 47.624 0 0 1 12 22.286a47.624 47.624 0 0 1 8.321-2.403A23.54 23.54 0 0 0 17.74 13.6M12 14.01v-.11" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 14.012V9.5M4.26 10.147 12 2.25l7.74 7.897" /></svg>
                                        @break
                                    @case('Field Study')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                                        @break
                                    @default
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="node-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                                @endswitch
                            </div>

                            {{-- Phase Header --}}
                            <div class="phase-header">
                                <div class="flex items-center gap-2.5">
                                    <h3 class="phase-title">{{ $displayLabel }} Courses</h3>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="rounded-full px-2.5 py-0.5 font-medium" style="background-color: {{ $phaseColor }}15; color: {{ $phaseColor }};">
                                        {{ $typeTotal }} {{ Str::plural('course', $typeTotal) }}
                                    </span>
                                    @if ($typeMinUnits)
                                        <span class="min-units-badge" style="background-color: {{ $phaseColor }}10; color: {{ $phaseColor }};">
                                            min {{ $typeMinUnits }} units required
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Courses within this phase --}}
                            <div class="phase-courses">
                                @foreach ($specializations as $specName => $courses)
                                    {{-- Specialization sub-header --}}
                                    @if (count($specializations) > 1 || $specName !== 'General')
                                        <div class="spec-subheader">
                                            <svg class="h-4 w-4 shrink-0 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                            </svg>
                                            <span>{{ $specName }}</span>
                                        </div>
                                    @endif

                                    @php
                                        $choiceGroups = [];
                                        $standaloneCourses = [];
                                        $msConditionalCourses = [];

                                        foreach ($courses as $course) {
                                            if ($course['choice_group'] !== null) {
                                                $choiceGroups[$course['choice_group']][] = $course;
                                            } elseif (!empty($course['ms_conditional'])) {
                                                $msConditionalCourses[] = $course;
                                            } else {
                                                $standaloneCourses[] = $course;
                                            }
                                        }
                                    @endphp

                                    <div class="course-grid">
                                        {{-- Choice groups --}}
                                        @foreach ($choiceGroups as $groupId => $groupCourses)
                                            <div class="choice-group">
                                                <div class="choice-label">
                                                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                                    Choose One
                                                </div>
                                                @foreach ($groupCourses as $idx => $course)
                                                    @if ($idx > 0)
                                                        <div class="choice-divider"><div class="choice-divider-or"><div class="choice-divider-line"></div><div class="choice-divider-text">OR</div><div class="choice-divider-line"></div></div></div>
                                                    @endif
                                                    @include('filament.pages.partials.compact-course-card', ['course' => $course, 'phaseColor' => $phaseColor, 'phaseColorLight' => $phaseColorLight])
                                                @endforeach
                                            </div>
                                        @endforeach

                                        {{-- Standalone courses --}}
                                        @foreach ($standaloneCourses as $course)
                                            @include('filament.pages.partials.compact-course-card', ['course' => $course, 'phaseColor' => $phaseColor, 'phaseColorLight' => $phaseColorLight])
                                        @endforeach

                                        {{-- MS-Conditional Group --}}
                                        @if (count($msConditionalCourses) > 0)
                                            <div class="ms-conditional-group">
                                                <div class="ms-conditional-banner">
                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                                    <span>Required if not taken in MS degree</span>
                                                </div>
                                                <div class="ms-conditional-courses">
                                                    @foreach ($msConditionalCourses as $course)
                                                        @include('filament.pages.partials.compact-course-card', ['course' => $course, 'phaseColor' => $phaseColor, 'phaseColorLight' => $phaseColorLight])
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Add Course Button (opens modal) --}}
                                        <div class="add-course-card-wrapper">
                                            <button
                                                x-on:click="$wire.set('addCourseDefaultType', '{{ strtolower(str_replace(['/', ' '], ['_', '_'], $courseType)) }}'); $nextTick(() => $wire.mountAction('addCourseMapping'))"
                                                class="add-course-card-lg"
                                                title="Add a course to {{ $displayLabel }}"
                                            >
                                                <div class="add-course-card-icon">
                                                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                    </svg>
                                                </div>
                                                <span class="add-course-card-text">Add Course</span>
                                                <span class="add-course-card-hint">Click to browse available courses</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            </div>
        @else
            {{-- No program selected --}}
            <x-filament::section>
                <div class="py-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-900/20">
                        <x-filament::icon icon="heroicon-o-map" class="h-6 w-6 text-primary-500"/>
                    </div>
                    <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Curriculum Map</h3>
                    <p class="mt-1 text-sm text-gray-500">Select a program above to view its full curriculum structure.</p>
                </div>
            </x-filament::section>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
