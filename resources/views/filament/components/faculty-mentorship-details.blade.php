<div class="space-y-6" x-data="{ }">

    {{-- ═══ Filter Indicator ═══ --}}
    @php
        $semesterIds = $semesterIds ?? [];
        $isFiltered = !empty($semesterIds);
        $filterLabels = [];
        if ($isFiltered) {
            $filterLabels = \App\Models\Semester::whereIn('id', $semesterIds)->get()->map(fn($s) => $s->label)->toArray();
        }

        // Role priority ordering (highest first)
        $rolePriority = ['Adviser' => 1, 'Co-Adviser' => 2, 'Chair' => 3, 'Co-Chair' => 4, 'Panel Member' => 5, 'Member' => 6,
                         'adviser' => 1, 'co-adviser' => 2, 'chair' => 3, 'co-chair' => 4, 'panel member' => 5, 'member' => 6];

        // Helper: group records by AY → Semester → Role
        // Returns: [ 'AY 2025-2026' => [ '2nd Semester' => [ 'Adviser' => [...], ... ], ... ], ... ]
        function groupByAySemRole($records, $rolePriority, $getTermFn) {
            $grouped = [];
            foreach ($records as $record) {
                $term = $getTermFn($record);
                $ayLabel = $term?->academicYear ? 'AY ' . $term->academicYear->year_start . '-' . ($term->academicYear->year_start + 1) : 'Unspecified AY';
                $semLabel = $term ? $term->label : 'Unspecified Term';
                $semCode = $term ? $term->term_code : '0000';
                $role = $record->role ?? 'Member';

                $grouped[$ayLabel][$semCode . '||' . $semLabel][$role][] = $record;
            }

            // Sort AY descending
            krsort($grouped);

            // Sort semesters descending within each AY, then sort roles by priority
            foreach ($grouped as $ay => &$semesters) {
                krsort($semesters);
                foreach ($semesters as $semKey => &$roles) {
                    uksort($roles, function($a, $b) use ($rolePriority) {
                        return ($rolePriority[strtolower($a)] ?? 99) <=> ($rolePriority[strtolower($b)] ?? 99);
                    });
                }
            }
            return $grouped;
        }
    @endphp

    @if($isFiltered)
    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-info-50 dark:bg-info-900/20 border border-info-200 dark:border-info-800">
        <x-heroicon-o-funnel class="w-4 h-4 text-info-500 shrink-0" />
        <span class="text-xs text-info-700 dark:text-info-300">
            Filtered by: <strong>{{ implode(', ', $filterLabels) }}</strong>
        </span>
    </div>
    @endif

    {{-- ═══ Section 1: Student Advisory Committee ═══ --}}
    <div class="space-y-2">
        <h3 class="text-lg font-medium tracking-tight text-gray-950 dark:text-white flex items-center gap-2">
            <x-heroicon-o-academic-cap class="w-5 h-5 text-primary-500" />
            Student Advisory Committee
        </h3>

        @php
            $scmQuery = $faculty->committeeMemberships()
                ->with(['student.program', 'termStart', 'termStart.academicYear', 'termEnd'])
                ->whereHas('student', fn($q) => $q->whereNull('deleted_at'));

            if ($isFiltered) {
                $scmQuery->where(function ($q) use ($semesterIds) {
                    $q->whereIn('term_start_id', $semesterIds)
                      ->orWhereIn('term_end_id', $semesterIds);
                });
            }

            $scmRecords = $scmQuery->get();
            $scmGrouped = groupByAySemRole($scmRecords, $rolePriority, fn($r) => $r->termStart);
        @endphp

        @if($scmRecords->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No student committee assignments{{ $isFiltered ? ' for this term' : '' }}.</p>
        @else
            @php $scmAyIndex = 0; @endphp
            @foreach($scmGrouped as $ayLabel => $semesters)
            <div x-data="{ open: {{ $scmAyIndex === 0 ? 'true' : 'false' }} }" class="border border-gray-200 dark:border-white/10 rounded-lg overflow-hidden">
                {{-- AY Header --}}
                <button type="button" @click.stop="open = !open" class="w-full flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors text-left">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                        <x-heroicon-o-folder class="w-4 h-4 text-primary-500" />
                        {{ $ayLabel }}
                        <span class="text-xs font-normal text-gray-400">({{ collect($semesters)->flatten(1)->count() }} assignments)</span>
                    </span>
                    <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 transition-transform" ::class="open ? 'rotate-180' : ''" />
                </button>

                <div x-show="open" x-collapse class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($semesters as $semKey => $roles)
                    @php $semLabel = explode('||', $semKey)[1] ?? $semKey; @endphp
                    <div x-data="{ semOpen: true }" class="bg-white dark:bg-gray-900">
                        {{-- Semester Header --}}
                        <button type="button" @click.stop="semOpen = !semOpen" class="w-full flex items-center justify-between px-6 py-2 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors text-left">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                {{ $semLabel }}
                            </span>
                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5 text-gray-400 transition-transform" ::class="semOpen ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="semOpen" x-collapse class="px-6 pb-3 space-y-2">
                            @foreach($roles as $role => $members)
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mb-1
                                    @if(in_array($role, ['Adviser', 'Co-Adviser'])) bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300
                                    @elseif(in_array($role, ['Chair', 'Co-Chair'])) bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300
                                    @else bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-300
                                    @endif">
                                    {{ $role }} ({{ count($members) }})
                                </span>
                                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                            <tr>
                                                <th class="px-3 py-1.5 font-medium">Student</th>
                                                <th class="px-3 py-1.5 font-medium">Student No.</th>
                                                <th class="px-3 py-1.5 font-medium">Program</th>
                                                <th class="px-3 py-1.5 font-medium">Level</th>
                                                <th class="px-3 py-1.5 font-medium">Enrolled</th>
                                                <th class="px-3 py-1.5 font-medium">Status</th>
                                                <th class="px-3 py-1.5 font-medium">Term</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                            @foreach($members as $cm)
                                            <tr>
                                                <td class="px-3 py-1.5 text-gray-950 dark:text-white">{{ $cm->student->full_name }}</td>
                                                <td class="px-3 py-1.5">
                                                    <a href="/admin/list-of-students?studentNumber={{ $cm->student->student_number }}"
                                                       class="text-primary-600 hover:text-primary-500 dark:text-primary-400 hover:underline font-mono text-xs"
                                                       target="_blank">
                                                        {{ \App\Models\Enrollee::formatStudentNumber($cm->student->student_number) }}
                                                    </a>
                                                </td>
                                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400">{{ $cm->student->program?->code ?? '—' }}</td>
                                                <td class="px-3 py-1.5">
                                                    @php $degreeLevel = $cm->student->program?->degree_level?->value; @endphp
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                                        @if($degreeLevel === 'doctorate') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                                        @elseif(in_array($degreeLevel, ['master', 'master_of_science'])) bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                                        @else bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-300
                                                        @endif">
                                                        @if($degreeLevel === 'doctorate') PhD
                                                        @elseif(in_array($degreeLevel, ['master', 'master_of_science'])) MS
                                                        @else —
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-3 py-1.5">
                                                    @php
                                                        $currentSem = \App\Models\Semester::where('is_current', true)->first();
                                                        $isEnrolled = $currentSem ? \App\Models\Enrollee::where('student_number', $cm->student->student_number)
                                                            ->where('term_id', $currentSem->term_code)->exists() : false;
                                                    @endphp
                                                    @if($isEnrolled)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300" title="Enrolled in {{ $currentSem?->label }}">✅ Yes</span>
                                                    @else
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-300" title="Not enrolled in {{ $currentSem?->label ?? 'current term' }}">⚠️ No</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-1.5">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                                        @if($cm->student->student_status === 'active') bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300
                                                        @elseif($cm->student->student_status === 'candidate') bg-info-100 text-info-800 dark:bg-info-900/30 dark:text-info-300
                                                        @elseif($cm->student->student_status === 'graduated') bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300
                                                        @else bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-300
                                                        @endif">
                                                        {{ ucfirst($cm->student->student_status ?? '—') }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                    @if($cm->termStart || $cm->termEnd)
                                                        {{ $cm->termStart?->label ?? '?' }} → {{ $cm->termEnd?->label ?? 'Present' }}
                                                    @else
                                                        <span class="italic">Unspecified</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @php $scmAyIndex++; @endphp
            @endforeach
        @endif
    </div>

    <hr class="border-gray-200 dark:border-gray-700">

    {{-- ═══ Section 2: Graduate Committee ═══ --}}
    <div class="space-y-2">
        <h3 class="text-lg font-medium tracking-tight text-gray-950 dark:text-white flex items-center gap-2">
            <x-heroicon-o-document-check class="w-5 h-5 text-warning-500" />
            Graduate Committee
        </h3>

        @php
            $gcmQuery = $faculty->graduateCommitteeMembers()
                ->with(['graduate.program', 'graduate.term', 'graduate.term.academicYear']);

            if ($isFiltered) {
                $gcmQuery->where(function ($q) use ($semesterIds) {
                    $q->whereIn('term_start_id', $semesterIds)
                      ->orWhereIn('term_end_id', $semesterIds);
                });
            }

            $gcmRecords = $gcmQuery->get();
            $gcmGrouped = groupByAySemRole($gcmRecords, $rolePriority, fn($r) => $r->graduate?->term);
        @endphp

        @if($gcmRecords->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No graduate committee assignments{{ $isFiltered ? ' for this term' : '' }}.</p>
        @else
            @php $gcmAyIndex = 0; @endphp
            @foreach($gcmGrouped as $ayLabel => $semesters)
            <div x-data="{ open: {{ $gcmAyIndex === 0 ? 'true' : 'false' }} }" class="border border-gray-200 dark:border-white/10 rounded-lg overflow-hidden">
                <button type="button" @click.stop="open = !open" class="w-full flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors text-left">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                        <x-heroicon-o-folder class="w-4 h-4 text-warning-500" />
                        {{ $ayLabel }}
                        <span class="text-xs font-normal text-gray-400">({{ collect($semesters)->flatten(1)->count() }} assignments)</span>
                    </span>
                    <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 transition-transform" ::class="open ? 'rotate-180' : ''" />
                </button>

                <div x-show="open" x-collapse class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($semesters as $semKey => $roles)
                    @php $semLabel = explode('||', $semKey)[1] ?? $semKey; @endphp
                    <div x-data="{ semOpen: true }" class="bg-white dark:bg-gray-900">
                        <button type="button" @click.stop="semOpen = !semOpen" class="w-full flex items-center justify-between px-6 py-2 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors text-left">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                {{ $semLabel }}
                            </span>
                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5 text-gray-400 transition-transform" ::class="semOpen ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="semOpen" x-collapse class="px-6 pb-3 space-y-2">
                            @foreach($roles as $role => $members)
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mb-1
                                    @if(in_array($role, ['Adviser', 'Co-Adviser'])) bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300
                                    @elseif(in_array($role, ['Chair', 'Co-Chair'])) bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300
                                    @else bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-300
                                    @endif">
                                    {{ $role }} ({{ count($members) }})
                                </span>
                                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                            <tr>
                                                <th class="px-3 py-1.5 font-medium w-[30%]">Graduate Name</th>
                                                <th class="px-3 py-1.5 font-medium w-[20%]">Student No.</th>
                                                <th class="px-3 py-1.5 font-medium w-[18%]">Degree</th>
                                                <th class="px-3 py-1.5 font-medium w-[32%]">Major / Field</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                            @foreach($members as $gm)
                                            <tr>
                                                <td class="px-3 py-1.5 text-gray-950 dark:text-white">{{ $gm->graduate?->name ?? $gm->name ?? 'Unknown' }}</td>
                                                <td class="px-3 py-1.5">
                                                    @if($gm->graduate?->student_number)
                                                        @php $gradStudent = \App\Models\Student::where('student_number', $gm->graduate->student_number)->first(); @endphp
                                                        @if($gradStudent)
                                                            <a href="/admin/list-of-students?studentNumber={{ $gm->graduate->student_number }}"
                                                               class="text-primary-600 hover:text-primary-500 dark:text-primary-400 hover:underline font-mono text-xs"
                                                               target="_blank">
                                                                {{ \App\Models\Enrollee::formatStudentNumber($gm->graduate->student_number) }}
                                                            </a>
                                                        @else
                                                            <span class="font-mono text-xs text-gray-500">{{ \App\Models\Enrollee::formatStudentNumber($gm->graduate->student_number) }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-gray-400">—</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400">{{ $gm->graduate?->degree ?? '—' }}</td>
                                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400">{{ $gm->graduate?->major_field_raw ?? '—' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @php $gcmAyIndex++; @endphp
            @endforeach
        @endif
    </div>

    <hr class="border-gray-200 dark:border-gray-700">

    {{-- ═══ Section 3: Academic Output Advisory ═══ --}}
    <div class="space-y-2">
        <h3 class="text-lg font-medium tracking-tight text-gray-950 dark:text-white flex items-center gap-2">
            <x-heroicon-o-book-open class="w-5 h-5 text-info-500" />
            Academic Output Advisory
        </h3>

        @php
            $aocQuery = $faculty->academicOutputCommitteeMembers()
                ->with(['academicOutput.student', 'termStart', 'termStart.academicYear', 'termEnd']);

            if ($isFiltered) {
                $aocQuery->where(function ($q) use ($semesterIds) {
                    $q->whereIn('term_start_id', $semesterIds)
                      ->orWhereIn('term_end_id', $semesterIds);
                });
            }

            $aocRecords = $aocQuery->get();
            $aocGrouped = groupByAySemRole($aocRecords, $rolePriority, fn($r) => $r->termStart);
        @endphp

        @if($aocRecords->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No academic output committee assignments{{ $isFiltered ? ' for this term' : '' }}.</p>
        @else
            @php $aocAyIndex = 0; @endphp
            @foreach($aocGrouped as $ayLabel => $semesters)
            <div x-data="{ open: {{ $aocAyIndex === 0 ? 'true' : 'false' }} }" class="border border-gray-200 dark:border-white/10 rounded-lg overflow-hidden">
                <button type="button" @click.stop="open = !open" class="w-full flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors text-left">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                        <x-heroicon-o-folder class="w-4 h-4 text-info-500" />
                        {{ $ayLabel }}
                        <span class="text-xs font-normal text-gray-400">({{ collect($semesters)->flatten(1)->count() }} assignments)</span>
                    </span>
                    <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 transition-transform" ::class="open ? 'rotate-180' : ''" />
                </button>

                <div x-show="open" x-collapse class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($semesters as $semKey => $roles)
                    @php $semLabel = explode('||', $semKey)[1] ?? $semKey; @endphp
                    <div x-data="{ semOpen: true }" class="bg-white dark:bg-gray-900">
                        <button type="button" @click.stop="semOpen = !semOpen" class="w-full flex items-center justify-between px-6 py-2 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors text-left">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                {{ $semLabel }}
                            </span>
                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5 text-gray-400 transition-transform" ::class="semOpen ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="semOpen" x-collapse class="px-6 pb-3 space-y-2">
                            @foreach($roles as $role => $members)
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mb-1 bg-info-100 text-info-800 dark:bg-info-900/30 dark:text-info-300">
                                    {{ ucwords(str_replace('-', ' ', $role)) }} ({{ count($members) }})
                                </span>
                                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                            <tr>
                                                <th class="px-3 py-1.5 font-medium w-[22%]">Student</th>
                                                <th class="px-3 py-1.5 font-medium w-[16%]">Student No.</th>
                                                <th class="px-3 py-1.5 font-medium w-[32%]">Output Title</th>
                                                <th class="px-3 py-1.5 font-medium w-[12%]">Type</th>
                                                <th class="px-3 py-1.5 font-medium w-[18%]">Term</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                            @foreach($members as $aom)
                                            <tr>
                                                <td class="px-3 py-1.5 text-gray-950 dark:text-white">{{ $aom->academicOutput?->student?->full_name ?? '—' }}</td>
                                                <td class="px-3 py-1.5">
                                                    @if($aom->academicOutput?->student)
                                                        <a href="/admin/list-of-students?studentNumber={{ $aom->academicOutput->student->student_number }}"
                                                           class="text-primary-600 hover:text-primary-500 dark:text-primary-400 hover:underline font-mono text-xs"
                                                           target="_blank">
                                                            {{ \App\Models\Enrollee::formatStudentNumber($aom->academicOutput->student->student_number) }}
                                                        </a>
                                                    @else
                                                        <span class="text-gray-400">—</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400" title="{{ $aom->academicOutput?->title }}">
                                                    {{ $aom->academicOutput?->title ?? '—' }}
                                                </td>
                                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400">{{ ucfirst($aom->academicOutput?->type ?? '—') }}</td>
                                                <td class="px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                    @if($aom->termStart || $aom->termEnd)
                                                        {{ $aom->termStart?->label ?? '?' }} → {{ $aom->termEnd?->label ?? 'Present' }}
                                                    @else
                                                        <span class="italic">Unspecified</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @php $aocAyIndex++; @endphp
            @endforeach
        @endif
    </div>

    {{-- ═══ Summary Stats ═══ --}}
    @php
        $totalStudents = $faculty->committeeMemberships()->distinct('student_id')->count('student_id');
        $totalGrad = $faculty->graduateCommitteeMembers()->distinct('graduate_id')->count('graduate_id');
        $totalAO = $faculty->academicOutputCommitteeMembers()->distinct('academic_output_id')->count('academic_output_id');
    @endphp
    <div class="flex items-center gap-4 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-4">
            <span><strong class="text-gray-700 dark:text-gray-300">{{ $totalStudents }}</strong> student assignments</span>
            <span>·</span>
            <span><strong class="text-gray-700 dark:text-gray-300">{{ $totalGrad }}</strong> graduate committees</span>
            <span>·</span>
            <span><strong class="text-gray-700 dark:text-gray-300">{{ $totalAO }}</strong> academic outputs</span>
        </div>
    </div>
</div>
