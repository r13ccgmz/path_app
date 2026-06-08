@php
    $metrics = $this->metrics;
    $violations = $this->violations;
    $chart = $this->chartData ?? ['labels' => [], 'phd' => [], 'masters' => []];
    $chartLabels = $chart['labels'] ?? [];
    $chartPhd = $chart['phd'] ?? [];
    $chartMasters = $chart['masters'] ?? [];
@endphp

<x-filament::widget>
    <div class="space-y-6">
        @if(!$onlyChart)
        {{-- Widget Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-academic-cap class="w-5 h-5 text-primary-500" />
                    Faculty Advisory & Eligibility Tracking Hub
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Tracks active advising loads and checks for academic eligibility compliance.
                </p>
            </div>
            @if(!empty($this->semesterIds))
                <span class="mt-2 md:mt-0 text-xs px-2.5 py-1 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-full font-medium border border-primary-100 dark:border-primary-800/50 flex items-center gap-1 w-fit">
                    <x-heroicon-m-funnel class="w-3 h-3" /> Filtered by Page Terms
                </span>
            @endif
        </div>

        {{-- Overview Metrics Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Metric 1 --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800/80 p-5 shadow-sm hover:shadow-md transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 tracking-wider block">Total Advisees</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1 block tracking-tight">{{ number_format($metrics['total_advisees']) }}</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 block">Unique students ({{ $metrics['total_assignments'] }} {{ \Illuminate\Support\Str::plural('assignment', $metrics['total_assignments']) }})</span>
                    </div>
                    <div class="p-3 bg-teal-50 dark:bg-teal-950/30 rounded-lg text-teal-600 group-hover:scale-110 transition-transform duration-300">
                        <x-heroicon-o-user-group class="w-6 h-6" />
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-teal-600/20 group-hover:bg-teal-600 transition-colors duration-300"></div>
            </div>

            {{-- Metric 2 --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800/80 p-5 shadow-sm hover:shadow-md transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 tracking-wider block">PhD Advisees</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1 block tracking-tight">{{ number_format($metrics['phd_advisees']) }}</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 block">Unique students ({{ $metrics['phd_assignments'] }} {{ \Illuminate\Support\Str::plural('assignment', $metrics['phd_assignments']) }})</span>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/30 rounded-lg text-blue-500 group-hover:scale-110 transition-transform duration-300">
                        <x-heroicon-o-academic-cap class="w-6 h-6" />
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-500/20 group-hover:bg-blue-500 transition-colors duration-300"></div>
            </div>

            {{-- Metric 3 --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800/80 p-5 shadow-sm hover:shadow-md transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 tracking-wider block">Master's / MS Advisees</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1 block tracking-tight">{{ number_format($metrics['masters_advisees']) }}</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 block">Unique students ({{ $metrics['masters_assignments'] }} {{ \Illuminate\Support\Str::plural('assignment', $metrics['masters_assignments']) }})</span>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 rounded-lg text-emerald-500 group-hover:scale-110 transition-transform duration-300">
                        <x-heroicon-o-book-open class="w-6 h-6" />
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500/20 group-hover:bg-emerald-500 transition-colors duration-300"></div>
            </div>

            {{-- Metric 4 --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800/80 p-5 shadow-sm hover:shadow-md transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 tracking-wider block">Active Advising Faculty</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1 block tracking-tight">{{ number_format($metrics['active_faculty']) }}</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 block">with Adviser/Co-Adviser roles</span>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-950/30 rounded-lg text-purple-500 group-hover:scale-110 transition-transform duration-300">
                        <x-heroicon-o-users class="w-6 h-6" />
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-purple-500/20 group-hover:bg-purple-500 transition-colors duration-300"></div>
            </div>
        </div>
        @endif

        {{-- Faculty Graduate Advising Load Chart --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl shadow-sm overflow-hidden" wire:key="eligibility-chart-{{ implode('-', $this->termFilter) }}-{{ $this->sortBy }}">
            <!-- Header with Filter -->
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-white/5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                        <x-heroicon-o-presentation-chart-bar class="w-4 h-4 text-emerald-500" />
                        Graduate Advising Load by Degree Level
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Active PhD vs. Master's/MS advisees per graduate faculty member.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto md:max-w-xl">
                    {{ $this->form }}
                </div>
            </div>

            <div class="p-6 space-y-6">
                @if(!empty($this->termFilter))
                    @php
                        $selectedTerms = array_map(fn($id) => $this->terms[$id] ?? 'Unknown', $this->termFilter);
                    @endphp
                    <div class="text-xs font-semibold px-3 py-1 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 rounded-md inline-block">
                        Showing snapshot only for {{ implode(', ', $selectedTerms) }}
                    </div>
                @endif

                <!-- Chart Container -->
                <div class="h-[600px] w-full"
                     x-data="{
                         chart: null,
                         isDark: false,
                         init() {
                             this.isDark = document.documentElement.classList.contains('dark');
                             const observer = new MutationObserver(() => {
                                 const dark = document.documentElement.classList.contains('dark');
                                 if (this.isDark !== dark) {
                                     this.isDark = dark;
                                     this.renderChart();
                                 }
                             });
                             observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                             this.waitForChart().then(() => {
                                 this.renderChart();
                                 this.$watch('$wire.chartData', (newData) => {
                                     if (this.chart && newData) {
                                         this.chart.data.labels = newData.labels || [];
                                         this.chart.data.datasets[0].data = newData.phd || [];
                                         this.chart.data.datasets[1].data = newData.masters || [];
                                         this.chart.update();
                                     }
                                 });
                                 window.addEventListener('refresh-eligibility-chart', () => {
                                     const newData = this.$wire.chartData;
                                     if (this.chart && newData) {
                                         this.chart.data.labels = newData.labels || [];
                                         this.chart.data.datasets[0].data = newData.phd || [];
                                         this.chart.data.datasets[1].data = newData.masters || [];
                                         this.chart.update();
                                     }
                                 });
                             });
                         },
                         waitForChart() {
                             return new Promise((resolve) => {
                                 if (window.Chart) return resolve();
                                 const check = setInterval(() => { if (window.Chart) { clearInterval(check); resolve(); } }, 50);
                             });
                         },
                         renderChart() {
                             if (this.chart) this.chart.destroy();
                             const isDarkTheme = document.documentElement.classList.contains('dark');
                             const textColor = isDarkTheme ? '#9ca3af' : '#4b5563';
                             const gridColor = isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.04)';
                             const fontFamily = 'Avenir, Helvetica Neue, Optima, sans-serif';
                             const chartData = this.$wire.chartData || { labels: [], phd: [], masters: [] };

                             this.chart = new Chart(this.$refs.canvas, {
                                 type: 'bar',
                                 data: {
                                     labels: chartData.labels || [],
                                     datasets: [
                                         {
                                             label: 'PhD Advisees',
                                             data: chartData.phd || [],
                                             backgroundColor: 'rgba(59, 130, 246, 0.85)',
                                             borderColor: 'rgba(59, 130, 246, 1)',
                                             borderWidth: 1,
                                             borderRadius: 4
                                         },
                                         {
                                             label: 'Master\'s / MS Advisees',
                                             data: chartData.masters || [],
                                             backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                             borderColor: 'rgba(16, 185, 129, 1)',
                                             borderWidth: 1,
                                             borderRadius: 4
                                         }
                                     ]
                                 },
                                 options: {
                                     responsive: true,
                                     maintainAspectRatio: false,
                                     scales: {
                                         x: {
                                             stacked: true,
                                             grid: { display: false },
                                             ticks: {
                                                 color: textColor,
                                                 font: { family: fontFamily, size: 10 },
                                                 maxRotation: 45,
                                                 minRotation: 30
                                             }
                                         },
                                         y: {
                                             stacked: true,
                                             beginAtZero: true,
                                             suggestedMax: 10,
                                             grid: { color: gridColor },
                                             ticks: {
                                                 color: textColor,
                                                 font: { family: fontFamily, size: 10 },
                                                 precision: 0
                                             }
                                         }
                                     },
                                     plugins: {
                                         legend: {
                                             position: 'top',
                                             labels: {
                                                 color: textColor,
                                                 font: { family: fontFamily, size: 11, weight: '500' },
                                                 usePointStyle: true,
                                                 pointStyle: 'circle'
                                             }
                                         },
                                         tooltip: {
                                             backgroundColor: isDarkTheme ? 'rgba(17, 24, 39, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                             titleColor: isDarkTheme ? '#ffffff' : '#111827',
                                             bodyColor: isDarkTheme ? '#d1d5db' : '#374151',
                                             borderColor: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                                             borderWidth: 1,
                                             titleFont: { family: fontFamily, size: 12, weight: 'bold' },
                                             bodyFont: { family: fontFamily, size: 12 },
                                             padding: 10,
                                             cornerRadius: 8
                                         }
                                     }
                                 }
                             });
                         }
                     }"
                >
                    <div class="relative w-full h-full">
                        <canvas x-ref="canvas" wire:ignore></canvas>
                    </div>
                </div>
            </div>
        </div>

        @if(!$onlyChart)
        {{-- Eligibility Audit Status Section --}}
        <div>
            @if(empty($violations))
                {{-- Completely Compliant State Card --}}
                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-950/20 dark:to-teal-950/10 border border-emerald-200/60 dark:border-emerald-900/30 rounded-xl p-5 shadow-sm flex items-center gap-4">
                    <div class="flex-shrink-0 p-3 bg-emerald-500 dark:bg-emerald-600 rounded-full text-white shadow-sm ring-4 ring-emerald-500/10 animate-pulse">
                        <x-heroicon-o-check class="w-6 h-6 stroke-[3]" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-400">Advisory Eligibility Compliant</h3>
                        <p class="text-xs text-emerald-700/90 dark:text-emerald-500/90 mt-1">
                            All active advisory assignments comply with graduate faculty highest-degree criteria. (Doctorate faculty advising doctorate candidates, Master's faculty advising Master's candidates).
                        </p>
                    </div>
                </div>
            @else
                {{-- Violations Mismatch Warning Card --}}
                <div class="border border-amber-200/60 dark:border-amber-900/30 bg-gradient-to-r from-amber-50/60 to-orange-50/20 dark:from-amber-950/10 dark:to-orange-950/5 rounded-xl p-5 shadow-sm space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 p-2 bg-amber-100 dark:bg-amber-900/40 rounded-lg text-amber-600 dark:text-amber-400">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-400">Advisory Eligibility Violations Detected ({{ count($violations) }})</h3>
                            <p class="text-xs text-amber-700 dark:text-amber-500 mt-1">
                                The following faculty members have active graduate advisee assignments that violate highest-degree requirements.
                            </p>
                        </div>
                    </div>

                    {{-- Dynamic Grid showing Mismatches --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        @foreach($violations as $facId => $v)
                            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 shadow-sm space-y-3">
                                {{-- Faculty Info Row --}}
                                <div class="flex justify-between items-start border-b border-gray-50 dark:border-gray-800 pb-2.5">
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-950 dark:text-gray-50">{{ $v['faculty_name'] }}</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Faculty Member</p>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-md font-bold uppercase tracking-wider">
                                        Degree: {{ $v['highest_degree'] }}
                                    </span>
                                </div>

                                {{-- Ineligible Assignments list --}}
                                <div class="space-y-2.5">
                                    @foreach($v['assignments'] as $asg)
                                        <div class="p-2.5 bg-rose-50/50 dark:bg-rose-950/10 border border-rose-100/50 dark:border-rose-900/20 rounded-lg space-y-1">
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ $asg['student_name'] }}</span>
                                                <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500">{{ $asg['student_number'] }}</span>
                                            </div>
                                            <div class="flex justify-between items-center text-[10px] text-gray-500 dark:text-gray-400">
                                                <span>Program: <strong class="text-gray-700 dark:text-gray-300">{{ $asg['program_code'] }}</strong> ({{ $asg['program_level'] }})</span>
                                                <span class="px-1.5 py-0.5 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 rounded text-[9px] font-medium">{{ $asg['role'] }}</span>
                                            </div>
                                            <p class="text-[10px] text-rose-600 dark:text-rose-400 pt-1 font-medium border-t border-rose-100/30 dark:border-rose-900/10 mt-1.5 flex items-start gap-1">
                                                <x-heroicon-m-x-circle class="w-3 h-3 mt-0.5 flex-shrink-0" />
                                                {{ $asg['reason'] }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @endif
    </div>
</x-filament::widget>
