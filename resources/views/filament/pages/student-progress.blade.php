<x-filament-panels::page>
    {{-- Helper Guide --}}
    <div class="rounded-xl border border-primary-200 dark:border-primary-800/50 bg-primary-50/50 dark:bg-primary-900/10 px-5 py-4 mb-6">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" />
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Student Progress</p>
                <p>Track academic progress by comparing units earned against program requirements. Click on a student number to view their full history. Use the <strong>Sync Student Data</strong> button to create student records from enrollment imports. Students who have earned 100% of required units are flagged as <strong>Candidates for Graduation</strong>.</p>
            </div>
        </div>
    </div>

    {{-- Summary Stats --}}
    @php
        $totalTracked = \App\Models\StudentProgram::whereHas('program', fn ($q) => $q->where('total_units_required', '>', 0))->count();
        $nearCompletion = \Illuminate\Support\Facades\DB::table('student_programs')
            ->join('programs', 'student_programs.program_id', '=', 'programs.id')
            ->where('programs.total_units_required', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) >= programs.total_units_required * 0.75', ['completed'])
            ->whereRaw('(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) < programs.total_units_required', ['completed'])
            ->count();
        $fullyCompleted = \Illuminate\Support\Facades\DB::table('student_programs')
            ->join('programs', 'student_programs.program_id', '=', 'programs.id')
            ->where('programs.total_units_required', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) >= programs.total_units_required', ['completed'])
            ->count();
        $graduatedCount = \App\Models\StudentProgram::where('status', 'graduated')->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10 p-4 bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Programs Tracked</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalTracked) }}</div>
        </div>
        <div class="rounded-xl ring-1 ring-amber-200 dark:ring-amber-800/50 p-4 bg-amber-50/50 dark:bg-amber-900/10 shadow-sm">
            <div class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Near Completion (≥75%)</div>
            <div class="text-2xl font-bold text-amber-700 dark:text-amber-300 mt-1">{{ number_format($nearCompletion) }}</div>
        </div>
        <div class="rounded-xl ring-1 ring-emerald-200 dark:ring-emerald-800/50 p-4 bg-emerald-50/50 dark:bg-emerald-900/10 shadow-sm">
            <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Candidates for Graduation</div>
            <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">{{ number_format($fullyCompleted) }}</div>
        </div>
        <div class="rounded-xl ring-1 ring-blue-200 dark:ring-blue-800/50 p-4 bg-blue-50/50 dark:bg-blue-900/10 shadow-sm">
            <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Graduated</div>
            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ number_format($graduatedCount) }}</div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
