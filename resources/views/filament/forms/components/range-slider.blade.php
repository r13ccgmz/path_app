<div x-data="{
        min: $wire.$entangle('{{ str_replace('.range', '.min', $getStatePath()) }}'),
        max: $wire.$entangle('{{ str_replace('.range', '.max', $getStatePath()) }}'),
        minval: 0,
        maxval: 100,
        init() {
            this.minval = this.min !== null && this.min !== undefined ? this.min : 0;
            this.maxval = this.max !== null && this.max !== undefined ? this.max : 100;
            
            this.$watch('minval', value => {
                value = parseInt(value);
                if (value > this.maxval) this.minval = this.maxval;
                this.min = this.minval;
            });
            this.$watch('maxval', value => {
                value = parseInt(value);
                if (value < this.minval) this.maxval = this.minval;
                this.max = this.maxval;
            });
        }
    }" 
    class="space-y-4">
    
    <div class="relative h-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
        @foreach([0, 25, 50, 75, 100] as $tick)
            <span class="absolute top-0 whitespace-nowrap" 
                  style="left: {{ $tick }}%; transform: {{ $tick === 0 ? 'translateX(0)' : ($tick === 100 ? 'translateX(-100%)' : 'translateX(-50%)') }};">
                {{ $tick }}%
            </span>
        @endforeach
    </div>

    <div class="relative w-full h-2 bg-gray-200 rounded-lg dark:bg-gray-700">
        <div class="absolute h-2 bg-primary-600 rounded-lg"
             x-bind:style="'left: ' + minval + '%; right: ' + (100 - maxval) + '%'"></div>
        
        <input type="range" min="0" max="100" step="1" x-model="minval"
               class="absolute w-full h-2 opacity-0 cursor-pointer pointer-events-none appearance-none"
               style="-webkit-appearance: none; pointer-events: none; z-index: 20;">
        <input type="range" min="0" max="100" step="1" x-model="maxval"
               class="absolute w-full h-2 opacity-0 cursor-pointer pointer-events-none appearance-none"
               style="-webkit-appearance: none; pointer-events: none; z-index: 21;">
               
        <style>
            input[type=range]::-webkit-slider-thumb {
                pointer-events: all;
                width: 16px;
                height: 16px;
                -webkit-appearance: none;
                border-radius: 50%;
                background: #16a34a;
                cursor: pointer;
            }
        </style>
    </div>

    <div class="flex justify-between items-center text-sm font-medium text-gray-700 dark:text-gray-300">
        <span x-text="minval + '%'"></span>
        <span>to</span>
        <span x-text="maxval + '%'"></span>
    </div>
</div>
