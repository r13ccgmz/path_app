<x-filament-widgets::widget>
    @php
        $overview = $this->getOverviewData();
    @endphp

    <x-filament::section class="fi-section-overview">
        <!-- Header with Filter -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 flex items-center justify-between">
            <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">Student Overview</h2>
            <div class="flex items-center gap-3 w-full max-w-xs">
                <div class="w-full">
                    {{ $this->form }}
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            @if(!empty($this->termFilter))
                @php
                    $selectedTerms = array_map(fn($id) => $this->terms[$id] ?? 'Unknown', $this->termFilter);
                @endphp
                <div class="text-xs font-semibold px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-md inline-block">
                    Showing snapshot only for {{ implode(', ', $selectedTerms) }}
                </div>
            @endif

            <!-- Stat Bar — compact horizontal metrics -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
                <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total</span>
                    <span class="text-xl font-bold text-gray-900 dark:text-white" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ number_format($overview['total']) }}</span>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Active</span>
                    <span class="text-xl font-bold text-green-600 dark:text-green-400" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ number_format($overview['active']) }}</span>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Graduated</span>
                    <span class="text-xl font-bold text-blue-700 dark:text-blue-400" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ number_format($overview['graduated']) }}</span>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">On Leave</span>
                    <span class="text-xl font-bold text-orange-600 dark:text-orange-400" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ number_format($overview['onLeave']) }}</span>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Inactive</span>
                    <span class="text-xl font-bold text-red-600 dark:text-red-400" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ number_format($overview['inactive'] ?? 0) }}</span>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Avg. Age</span>
                    <span class="text-xl font-bold text-gray-900 dark:text-white" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ $overview['averageAge'] ?: 'N/A' }}</span>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Avg. Terms</span>
                    <span class="text-xl font-bold text-gray-900 dark:text-white" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ $overview['averageTerms'] }}</span>
                </div>
            </div>

            <!-- Program Distribution Chart -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5 shadow-sm" wire:key="overview-chart-{{ empty($this->termFilter) ? 'all' : implode('-', $this->termFilter) }}">
                <div class="mb-4">
                    <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-white mb-1">Program Enrollment</h3>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Distribution of students across degree programs.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-8 lg:gap-12 w-full min-h-[260px]">
                    <div class="relative w-full max-w-[220px] aspect-square flex items-center justify-center" wire:ignore>
                        <div class="w-full h-full"
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
                                            if (this.chart && newData && newData.chart) {
                                                this.chart.data.labels = newData.chart.labels || [];
                                                this.chart.data.datasets[0].data = newData.chart.data || [];
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
                                    const chartData = this.$wire.chartData || { chart: { labels: [], data: [], colors: [] }, total: 0 };

                                    this.chart = new Chart(this.$refs.canvas, {
                                        type: 'doughnut',
                                        data: {
                                            labels: chartData.chart.labels || [],
                                            datasets: [{
                                                data: chartData.chart.data || [],
                                                backgroundColor: chartData.chart.colors || [],
                                                borderWidth: 2,
                                                borderColor: isDarkTheme ? '#111827' : '#ffffff',
                                                hoverOffset: 6
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            cutout: '72%',
                                            layout: { padding: 10 },
                                            plugins: {
                                                legend: { display: false },
                                                tooltip: {
                                                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                                                    titleFont: { size: 13, family: fontFamily },
                                                    bodyFont: { size: 14, weight: 'bold', family: fontFamily },
                                                    padding: 12,
                                                    cornerRadius: 8,
                                                    callbacks: {
                                                        label: function(context) {
                                                            return ' ' + context.label + ': ' + context.raw + ' students';
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        plugins: [{
                                            id: 'centerText',
                                            beforeDraw: (chart) => {
                                                var width = chart.width, height = chart.height, ctx = chart.ctx;
                                                ctx.restore();
                                                var fontSize = (height / 100).toFixed(2);
                                                ctx.font = 'bold ' + fontSize + 'em ' + fontFamily;
                                                ctx.textBaseline = 'middle';
                                                var text = this.$wire.chartData.total !== undefined ? this.$wire.chartData.total : '0',
                                                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                                                    textY = height / 2 - 8;
                                                ctx.fillStyle = isDarkTheme ? '#ffffff' : '#111827';
                                                ctx.fillText(text, textX, textY);

                                                ctx.font = '600 ' + (fontSize * 0.35) + 'em ' + fontFamily;
                                                var subText = 'TOTAL',
                                                    subTextX = Math.round((width - ctx.measureText(subText).width) / 2),
                                                    subTextY = height / 2 + 18;
                                                ctx.fillStyle = isDarkTheme ? '#9ca3af' : '#6b7280';
                                                ctx.fillText(subText, subTextX, subTextY);
                                                ctx.save();
                                            }
                                        }]
                                    });
                                }
                            }"
                        >
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    </div>

                    <!-- Custom Legend -->
                    <div class="flex-1 w-full max-w-xs space-y-2.5 pl-4 sm:pl-0 sm:border-l border-gray-200 dark:border-white/10 sm:ml-4" x-data="{ expanded: false }">
                        @foreach(array_slice($overview['chart']['labels'], 0, 6) as $index => $label)
                        <div class="flex items-center justify-between group">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $overview['chart']['colors'][$index] }}"></span>
                                <span class="text-sm font-medium text-gray-700 dark:text-white leading-tight">{{ $label }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $overview['chart']['data'][$index] }}
                                </span>
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 w-10 text-right">
                                    {{ round(($overview['chart']['data'][$index] / max($overview['total'], 1)) * 100, 1) }}%
                                </span>
                            </div>
                        </div>
                        @endforeach

                        @if(count($overview['chart']['labels']) > 6)
                            <div x-show="expanded" x-collapse class="space-y-2.5 mt-2.5">
                                @foreach(array_slice($overview['chart']['labels'], 6) as $index => $label)
                                    @php $realIndex = $index + 6; @endphp
                                    <div class="flex items-center justify-between group">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $overview['chart']['colors'][$realIndex] }}"></span>
                                            <span class="text-sm font-medium text-gray-700 dark:text-white leading-tight">{{ $label }}</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $overview['chart']['data'][$realIndex] }}
                                            </span>
                                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 w-10 text-right">
                                                {{ round(($overview['chart']['data'][$realIndex] / max($overview['total'], 1)) * 100, 1) }}%
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="pt-2 mt-2 border-t border-gray-200 dark:border-white/5">
                                <button type="button" @click="expanded = !expanded" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 flex items-center gap-2 transition-colors">
                                    <span class="w-8 border-b border-gray-300 dark:border-gray-600"></span>
                                    <span x-text="expanded ? 'Hide extra programs' : '+ {{ count($overview['chart']['labels']) - 6 }} more programs'"></span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
