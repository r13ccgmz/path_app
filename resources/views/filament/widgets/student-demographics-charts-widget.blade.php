<x-filament-widgets::widget>
    @php
        $demo = $this->getDemographicsData();
    @endphp

    <x-filament::section class="fi-section-demographics shadow-sm border border-gray-200 dark:border-white/10 rounded-xl p-0 overflow-hidden">
        
        <!-- Header with Filter -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 flex items-center justify-between">
            <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">Student Demographics</h2>
            
            <div class="flex items-center gap-3 w-full max-w-xs">
                <div class="w-full">
                    {{ $this->form }}
                </div>
            </div>
        </div>

        <div class="p-6">
            @if(!empty($this->termFilter))
                @php
                    $selectedTerms = array_map(fn($id) => $this->terms[$id] ?? 'Unknown', $this->termFilter);
                @endphp
                <div class="mb-4 text-xs font-semibold px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-md inline-block">
                    Showing data only for {{ implode(', ', $selectedTerms) }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" wire:key="demographics-charts-{{ empty($this->termFilter) ? 'all' : implode('-', $this->termFilter) }}">
        

        <!-- Gender Chart -->
        <div class="fi-section-chart bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5 shadow-sm flex flex-col">
            <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-white mb-1">Gender Profile</h3>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6">Ratio of male to female students.</p>
            
            <div class="flex-1 flex items-center justify-center min-h-[220px]" wire:ignore x-data="{
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
                            if (this.chart && newData && newData.gender) {
                                this.chart.data.labels = newData.gender.labels || [];
                                this.chart.data.datasets[0].data = newData.gender.data || [];
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
                    const fontFamily = 'Avenir, Helvetica Neue, Optima, sans-serif';
                    const chartData = this.$wire.chartData || { gender: { labels: [], data: [], colors: [] } };
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'pie',
                        data: {
                            labels: chartData.gender.labels || [],
                            datasets: [{
                                data: chartData.gender.data || [],
                                backgroundColor: chartData.gender.colors || [],
                                borderWidth: 2,
                                borderColor: isDarkTheme ? '#111827' : '#ffffff',
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            layout: { padding: 10 },
                            plugins: { 
                                legend: { position: 'bottom', labels: { color: isDarkTheme ? '#ffffff' : '#374151', padding: 20, boxWidth: 10, usePointStyle: true, font: { size: 12, weight: '600', family: fontFamily } } },
                                tooltip: {
                                    backgroundColor: 'rgba(17, 24, 39, 0.9)', padding: 10, cornerRadius: 8,
                                    titleFont: { family: fontFamily }, bodyFont: { family: fontFamily },
                                    callbacks: { label: function(c) { return ' ' + c.label + ': ' + c.raw; } }
                                }
                            }
                        }
                    });
                }
            }">
                <div class="relative w-full h-full"><canvas x-ref="canvas"></canvas></div>
            </div>
        </div>

        <!-- Enrollment Status Chart -->
        <div class="fi-section-chart bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5 shadow-sm flex flex-col">
            <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-white mb-1">Academic Load</h3>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6">Full-time (>= {{ $demo['enrollment']['threshold'] }} units) vs Part-time.</p>
            
            <div class="flex-1 flex items-center justify-center min-h-[220px]" wire:ignore x-data="{
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
                            if (this.chart && newData && newData.enrollment) {
                                this.chart.data.labels = newData.enrollment.labels || [];
                                this.chart.data.datasets[0].data = newData.enrollment.data || [];
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
                    const fontFamily = 'Avenir, Helvetica Neue, Optima, sans-serif';
                    const chartData = this.$wire.chartData || { enrollment: { labels: [], data: [], colors: [] } };
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'pie',
                        data: {
                            labels: chartData.enrollment.labels || [],
                            datasets: [{
                                data: chartData.enrollment.data || [],
                                backgroundColor: chartData.enrollment.colors || [],
                                borderWidth: 2,
                                borderColor: isDarkTheme ? '#111827' : '#ffffff',
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            layout: { padding: 10 },
                            plugins: { 
                                legend: { position: 'bottom', labels: { color: isDarkTheme ? '#ffffff' : '#374151', padding: 20, boxWidth: 10, usePointStyle: true, font: { size: 12, weight: '600', family: fontFamily } } },
                                tooltip: {
                                    backgroundColor: 'rgba(17, 24, 39, 0.9)', padding: 10, cornerRadius: 8,
                                    titleFont: { family: fontFamily }, bodyFont: { family: fontFamily },
                                    callbacks: { label: function(c) { return ' ' + c.label + ': ' + c.raw; } }
                                }
                            }
                        }
                    });
                }
            }">
                <div class="relative w-full h-full"><canvas x-ref="canvas"></canvas></div>
            </div>
        </div>

        <!-- Nationality Chart -->
        <div class="fi-section-chart bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5 shadow-sm flex flex-col">
            <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-white mb-1">Nationality</h3>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6">Local vs International.</p>
            
            <div class="flex-1 flex items-center justify-center min-h-[220px]" wire:ignore x-data="{
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
                            if (this.chart && newData && newData.nationality && newData.nationality.summary) {
                                this.chart.data.labels = newData.nationality.summary.labels || [];
                                this.chart.data.datasets[0].data = newData.nationality.summary.data || [];
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
                    const fontFamily = 'Avenir, Helvetica Neue, Optima, sans-serif';
                    const chartData = this.$wire.chartData || { nationality: { summary: { labels: [], data: [], colors: [] } } };
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'pie',
                        data: {
                            labels: chartData.nationality.summary.labels || [],
                            datasets: [{
                                data: chartData.nationality.summary.data || [],
                                backgroundColor: chartData.nationality.summary.colors || [],
                                borderWidth: 2,
                                borderColor: isDarkTheme ? '#111827' : '#ffffff',
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            layout: { padding: 10 },
                            plugins: { 
                                legend: { position: 'bottom', labels: { color: isDarkTheme ? '#ffffff' : '#374151', padding: 20, boxWidth: 10, usePointStyle: true, font: { size: 12, weight: '600', family: fontFamily } } },
                                tooltip: {
                                    backgroundColor: 'rgba(17, 24, 39, 0.9)', padding: 10, cornerRadius: 8,
                                    titleFont: { family: fontFamily }, bodyFont: { family: fontFamily },
                                    callbacks: { label: function(c) { return ' ' + c.label + ': ' + c.raw; } }
                                }
                            }
                        }
                    });
                }
            }">
                <div class="relative w-full h-full"><canvas x-ref="canvas"></canvas></div>
            </div>
            @if(count($demo['nationality']['breakdown']) > 0)
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/10">
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Intl Breakdown</p>
                <div class="flex flex-wrap gap-1.5 max-h-[120px] overflow-y-auto pr-1 pb-1">
                    @foreach($demo['nationality']['breakdown'] as $country => $count)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-gray-50 border border-gray-200 text-gray-700 dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                            {{ $country }}: {{ $count }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
