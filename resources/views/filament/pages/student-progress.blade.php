<x-filament-panels::page>
    {{-- Helper Guide --}}
    @php
        $gradThreshold = (int) \App\Models\SystemSetting::get('graduation_candidate_threshold', 100);
        $thresholdDecimal = $gradThreshold / 100;
        
        if ($gradThreshold > 75) {
            $nearLowerBound = 75;
        } else {
            $nearLowerBound = max(0, $gradThreshold - 25);
        }
        $nearLowerBoundDecimal = $nearLowerBound / 100;

        $totalTracked = \App\Models\StudentProgram::whereHas('program', fn ($q) => $q->where('total_units_required', '>', 0))->count();
        
        $nearCompletion = \Illuminate\Support\Facades\DB::table('student_programs')
            ->join('programs', 'student_programs.program_id', '=', 'programs.id')
            ->where('programs.total_units_required', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) >= programs.total_units_required * ?', ['completed', $nearLowerBoundDecimal])
            ->whereRaw('(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) < programs.total_units_required * ?', ['completed', $thresholdDecimal])
            ->count();
            
        $candidateCount = \Illuminate\Support\Facades\DB::table('student_programs')
            ->join('programs', 'student_programs.program_id', '=', 'programs.id')
            ->where('programs.total_units_required', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) >= programs.total_units_required * ?', ['completed', $thresholdDecimal])
            ->count();
            
        $graduatedCount = \App\Models\StudentProgram::where('status', 'graduated')->count();
    @endphp

    <div class="relative overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800/60 bg-gradient-to-r from-primary-500/10 via-primary-600/5 to-transparent dark:from-primary-500/10 dark:via-primary-900/5 dark:to-transparent px-6 py-5 mb-8 shadow-sm backdrop-blur-md">
        <!-- Accent decorative glows -->
        <div class="absolute -right-16 -top-16 w-32 h-32 rounded-full bg-primary-400/20 blur-2xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-32 h-32 rounded-full bg-blue-400/10 blur-2xl pointer-events-none"></div>
        
        <div class="flex items-start gap-4 relative z-10">
            <div class="p-3 rounded-xl bg-primary-500/10 text-primary-600 dark:text-primary-400 shrink-0 shadow-inner">
                <x-heroicon-o-academic-cap class="w-6 h-6 animate-pulse" />
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-300">
                <h1 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1 tracking-tight">Student Progress & Graduation Readiness</h1>
                <p class="leading-relaxed">
                    Monitor academic milestones by evaluating student course credits against degree requirements. 
                    Enrollees with <strong>{{ $gradThreshold }}%</strong> or higher completion are categorized as <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Candidates for Graduation</span>. 
                    Click any student number to drill down into their complete history, or click <strong class="text-gray-800 dark:text-gray-200">Sync Student Data</strong> to import enrollees.
                </p>
            </div>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Programs Tracked --}}
        <div class="group relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-850 bg-white dark:bg-gray-900 p-5 shadow-sm transition-all duration-300 hover:scale-[1.02] hover:-translate-y-0.5 hover:shadow-md border-l-4 border-l-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Programs Tracked</div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2 tracking-tight">{{ number_format($totalTracked) }}</div>
                </div>
                <div class="p-3 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-500 transition-colors duration-300 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/50">
                    <x-heroicon-o-folder-open class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- Near Completion --}}
        <div class="group relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-850 bg-white dark:bg-gray-900 p-5 shadow-sm transition-all duration-300 hover:scale-[1.02] hover:-translate-y-0.5 hover:shadow-md border-l-4 border-l-amber-500">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Near Completion <span class="text-amber-600 dark:text-amber-400">(≥{{ $nearLowerBound }}% & <{{ $gradThreshold }}%)</span>
                    </div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2 tracking-tight">{{ number_format($nearCompletion) }}</div>
                </div>
                <div class="p-3 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-500 transition-colors duration-300 group-hover:bg-amber-100 dark:group-hover:bg-amber-900/50">
                    <x-heroicon-o-clock class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- Candidates for Graduation --}}
        <div class="group relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-850 bg-white dark:bg-gray-900 p-5 shadow-sm transition-all duration-300 hover:scale-[1.02] hover:-translate-y-0.5 hover:shadow-md border-l-4 border-l-emerald-500">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Candidates for Graduation (≥{{ $gradThreshold }}%)</div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2 tracking-tight">{{ number_format($candidateCount) }}</div>
                </div>
                <div class="p-3 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-500 transition-colors duration-300 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/50">
                    <x-heroicon-o-academic-cap class="w-6 h-6" />
                </div>
            </div>
        </div>

        {{-- Graduated --}}
        <div class="group relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-850 bg-white dark:bg-gray-900 p-5 shadow-sm transition-all duration-300 hover:scale-[1.02] hover:-translate-y-0.5 hover:shadow-md border-l-4 border-l-indigo-500">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Graduated</div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2 tracking-tight">{{ number_format($graduatedCount) }}</div>
                </div>
                <div class="p-3 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 transition-colors duration-300 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50">
                    <x-heroicon-o-check-badge class="w-6 h-6" />
                </div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
