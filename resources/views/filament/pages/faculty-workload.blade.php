<x-filament-panels::page>
    @php
        $stats = $this->getWorkloadStats();
    @endphp

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Offerings</span>
            <span class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ number_format($stats['totalOfferings']) }}</span>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Faculty Teaching</span>
            <span class="text-2xl font-bold text-green-600 dark:text-green-400" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ number_format($stats['uniqueFaculty']) }}</span>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Avg. per Faculty</span>
            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ $stats['avgPerFaculty'] }}</span>
        </div>
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm p-4 flex flex-col items-center text-center">
            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">No Courses</span>
            <span class="text-2xl font-bold text-red-600 dark:text-red-400" style="font-family: Avenir, 'Helvetica Neue', Optima, sans-serif;">{{ number_format($stats['noCourseFaculty']) }}</span>
        </div>
    </div>

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>
