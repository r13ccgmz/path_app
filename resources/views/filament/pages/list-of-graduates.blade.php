<x-filament-panels::page>
    {{-- Helper Guide --}}
    <div class="rounded-xl border border-primary-200 dark:border-primary-800/50 bg-primary-50/50 dark:bg-primary-900/10 px-5 py-4 mb-6">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" />
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="font-semibold text-gray-800 dark:text-gray-200 mb-1">List of Graduates</p>
                <p>Manage graduate records imported from the Graduate School. Use <strong>Upload Graduates</strong> to import CSV data and <strong>Auto-Match</strong> to link graduates to their corresponding student records (with configurable similarity threshold). Click on a matched student number to view their full history.</p>
                <p class="mt-1 text-xs">Use the <strong>Assign</strong> or <strong>Find</strong> actions per row for manual matching. Use <strong>Unmatch</strong> to remove individual links, or <strong>Remove All Auto Links</strong> to clear all auto-matched links at once.</p>
            </div>
        </div>
    </div>

    {{-- Summary Stats --}}
    @php
        $totalGraduates = \App\Models\Graduate::count();
        $matchedGraduates = \App\Models\Graduate::whereNotNull('student_number')->where('student_number', '!=', '')->count();
        $autoMatched = \App\Models\Graduate::where('match_type', 'auto')->whereNotNull('student_number')->where('student_number', '!=', '')->count();
        $manualMatched = \App\Models\Graduate::where('match_type', 'manual')->whereNotNull('student_number')->where('student_number', '!=', '')->count();
        $unmatchedGraduates = $totalGraduates - $matchedGraduates;
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 p-4 bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Graduates</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalGraduates) }}</div>
        </div>
        <div class="rounded-xl ring-1 ring-green-200 dark:ring-green-800/50 p-4 bg-green-50/50 dark:bg-green-900/10 shadow-sm">
            <div class="text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider">Matched</div>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">{{ number_format($matchedGraduates) }}</div>
        </div>
        <div class="rounded-xl ring-1 ring-blue-200 dark:ring-blue-800/50 p-4 bg-blue-50/50 dark:bg-blue-900/10 shadow-sm">
            <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Auto</div>
            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ number_format($autoMatched) }}</div>
        </div>
        <div class="rounded-xl ring-1 ring-emerald-200 dark:ring-emerald-800/50 p-4 bg-emerald-50/50 dark:bg-emerald-900/10 shadow-sm">
            <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Manual</div>
            <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">{{ number_format($manualMatched) }}</div>
        </div>
        <div class="rounded-xl ring-1 ring-amber-200 dark:ring-amber-800/50 p-4 bg-amber-50/50 dark:bg-amber-900/10 shadow-sm">
            <div class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Unmatched</div>
            <div class="text-2xl font-bold text-amber-700 dark:text-amber-300 mt-1">{{ number_format($unmatchedGraduates) }}</div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
