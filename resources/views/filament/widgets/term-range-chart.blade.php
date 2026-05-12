@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $isCollapsible = $this->isCollapsible();
    $type = $this->getType();

    // Term map for tooltips (only available if using HasTermRangeFilter)
    $termMap = property_exists($this, 'termDescriptions') ? $this->termDescriptions : [];
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5 shadow-sm flex flex-col h-full">
        <div class="mb-4">
            <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-white mb-1">{{ $heading }}</h3>
            @if($description)
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
        <div
            class="flex-1 flex items-center justify-center min-h-[280px]"
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
            <div
                class="w-full h-full relative"
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                data-chart-type="{{ $type }}"
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            maxHeight: @js($maxHeight = $this->getMaxHeight()),
                            options: Object.assign(@js($this->getOptions()), {
                                plugins: Object.assign((@js($this->getOptions())).plugins || {}, {
                                    tooltip: {
                                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                                        titleFont: { size: 13, family: 'Avenir, Helvetica Neue, Optima, sans-serif' },
                                        bodyFont: { size: 14, weight: 'bold', family: 'Avenir, Helvetica Neue, Optima, sans-serif' },
                                        padding: 12,
                                        cornerRadius: 8,
                                        callbacks: {
                                            title: function(tooltipItems) {
                                                if (!tooltipItems || tooltipItems.length === 0) return '';
                                                var label = tooltipItems[0].label || '';
                                                var map = @js($termMap);
                                                var desc = map[label] || '';
                                                return desc ? [label, desc] : label;
                                            }
                                        }
                                    }
                                })
                            }),
                            type: @js($type),
                        })"
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight)
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
