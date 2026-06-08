<x-filament-widgets::widget class="fi-wi-chart">
    @php
        $progress = $this->getProgressData();
    @endphp

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5 shadow-sm flex flex-col h-full w-full"
         wire:key="academic-progress-{{ md5(json_encode($progress)) }}">
        <div class="mb-6">
            <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-white mb-1">Academic Progress</h3>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Completion % based on units earned vs. required.</p>
        </div>
        
        <div class="flex-1 flex flex-col sm:flex-row items-center justify-center gap-6 lg:gap-8 w-full h-full">
            <div class="relative w-full max-w-[200px] aspect-square flex items-center justify-center" wire:ignore>
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
                                    if (this.chart && newData) {
                                        this.chart.data.labels = newData.labels || [];
                                        this.chart.data.datasets[0].data = newData.data || [];
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
                            const chartData = this.$wire.chartData || { labels: [], data: [], colors: [], total: 0 };
                            
                            this.chart = new Chart(this.$refs.canvas, {
                                type: 'doughnut',
                                data: {
                                    labels: chartData.labels || [],
                                    datasets: [{
                                        data: chartData.data || [],
                                        backgroundColor: chartData.colors || [],
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
                                        var subText = 'STUDENTS',
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
            <div class="flex-1 w-full max-w-[200px] space-y-2.5 sm:border-l border-gray-200 dark:border-white/10 sm:pl-4">
                @foreach($progress['labels'] as $index => $label)
                <div class="flex items-center justify-between group">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $progress['colors'][$index] }}"></span>
                        <span class="text-xs font-medium text-gray-700 dark:text-white leading-tight">{{ $label }}</span>
                    </div>
                    <span class="text-xs font-bold text-gray-900 dark:text-white">
                        {{ $progress['data'][$index] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
