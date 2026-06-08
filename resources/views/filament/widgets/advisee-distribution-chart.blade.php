<x-filament-widgets::widget>
    @php
        $chartData = $this->getChartData();
    @endphp

    <x-filament::section class="fi-section-advisee-distribution">
        <!-- Header with Filter -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">Faculty Assignment Load</h2>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Stacked bar chart of committee assignments.</p>
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
                <div class="text-xs font-semibold px-3 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-md inline-block">
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
                                     this.chart.data.datasets[0].data = newData.studentData || [];
                                     this.chart.data.datasets[1].data = newData.gradData || [];
                                     this.chart.data.datasets[2].data = newData.aoData || [];
                                     this.chart.update();
                                 }
                             });
                             window.addEventListener('refresh-advisee-chart', () => {
                                 const newData = this.$wire.chartData;
                                 if (this.chart && newData) {
                                     this.chart.data.labels = newData.labels || [];
                                     this.chart.data.datasets[0].data = newData.studentData || [];
                                     this.chart.data.datasets[1].data = newData.gradData || [];
                                     this.chart.data.datasets[2].data = newData.aoData || [];
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
                         const chartData = this.$wire.chartData || { labels: [], studentData: [], gradData: [], aoData: [] };

                         this.chart = new Chart(this.$refs.canvas, {
                             type: 'bar',
                             data: {
                                 labels: chartData.labels || [],
                                 datasets: [
                                     {
                                         label: 'Student Advisory',
                                         data: chartData.studentData || [],
                                         backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                         borderColor: 'rgba(16, 185, 129, 1)',
                                         borderWidth: 1,
                                         borderRadius: 4,
                                     },
                                     {
                                         label: 'Graduate Committee',
                                         data: chartData.gradData || [],
                                         backgroundColor: 'rgba(245, 158, 11, 0.85)',
                                         borderColor: 'rgba(245, 158, 11, 1)',
                                         borderWidth: 1,
                                         borderRadius: 4,
                                     },
                                     {
                                         label: 'Academic Output',
                                         data: chartData.aoData || [],
                                         backgroundColor: 'rgba(59, 130, 246, 0.85)',
                                         borderColor: 'rgba(59, 130, 246, 1)',
                                         borderWidth: 1,
                                         borderRadius: 4,
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
                                             minRotation: 30,
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
                <div class="w-full h-full">
                    <canvas x-ref="canvas" wire:ignore></canvas>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
