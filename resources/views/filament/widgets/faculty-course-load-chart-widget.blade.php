@php
    $chart = $this->chartData ?? ['labels' => [], 'courses' => []];
    $chartLabels = $chart['labels'] ?? [];
    $chartCourses = $chart['courses'] ?? [];
@endphp

<x-filament::widget>
    <div class="space-y-6">
        {{-- Faculty Course Load Chart --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl shadow-sm overflow-hidden" wire:key="course-load-chart-{{ $this->fromSemesterId }}-{{ $this->toSemesterId }}-{{ $this->sortBy }}">
            <!-- Header with Filter -->
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-white/5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                        <x-heroicon-o-presentation-chart-bar class="w-4 h-4 text-primary-500" />
                        Faculty Course Load Overview
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Number of course offerings assigned per faculty member in the selected semester range.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto md:max-w-2xl">
                    {{ $this->form }}
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Chart Container -->
                <div class="h-[450px] w-full"
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
                                         this.chart.data.datasets[0].data = newData.courses || [];
                                         this.chart.update();
                                     }
                                 });
                                 window.addEventListener('refresh-course-load-chart', () => {
                                     const newData = this.$wire.chartData;
                                     if (this.chart && newData) {
                                         this.chart.data.labels = newData.labels || [];
                                         this.chart.data.datasets[0].data = newData.courses || [];
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
                             const chartData = this.$wire.chartData || { labels: [], courses: [] };

                             this.chart = new Chart(this.$refs.canvas, {
                                 type: 'bar',
                                 data: {
                                     labels: chartData.labels || [],
                                     datasets: [
                                         {
                                             label: 'Assigned Courses',
                                             data: chartData.courses || [],
                                             backgroundColor: 'rgba(59, 130, 246, 0.85)',
                                             borderColor: 'rgba(59, 130, 246, 1)',
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
                                             grid: { display: false },
                                             ticks: {
                                                 color: textColor,
                                                 font: { family: fontFamily, size: 10 },
                                                 maxRotation: 45,
                                                 minRotation: 30
                                             }
                                         },
                                         y: {
                                             beginAtZero: true,
                                             suggestedMax: 5,
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
    </div>
</x-filament::widget>
