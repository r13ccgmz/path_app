{{-- Academic Progress per-program partial - receives $progress array --}}
<div class="mb-6">
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-3">
            @if ($progress['admission_semester'] ?? null)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Admitted: {{ $progress['admission_semester'] }}
                </span>
            @endif
            @if(isset($progress['student_program_id']) && $progress['student_program_id'] !== null)
                @if($progress['student_program_id'] === 'fallback')
                    <button wire:click="removeFallbackProgram"
                        wire:confirm="Are you sure you want to remove this program assignment?"
                        class="text-red-500 hover:text-red-700 transition-colors text-xs flex items-center gap-1" title="Remove Program">
                        <x-heroicon-o-trash class="w-4 h-4" /> Remove Program
                    </button>
                @else
                    <button wire:click="deleteProgram({{ $progress['student_program_id'] }})"
                        wire:confirm="Are you sure you want to remove this academic program and all its linked enrollments?"
                        class="text-red-500 hover:text-red-700 transition-colors text-xs flex items-center gap-1" title="Remove Program">
                        <x-heroicon-o-trash class="w-4 h-4" /> Remove Program
                    </button>
                @endif
            @endif
        </div>
        <div class="flex items-center gap-6">
            @if ($progress['gwa'] ?? null)
                <div class="text-sm text-gray-600 dark:text-gray-400 border-r border-gray-300 dark:border-gray-600 pr-6">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider mr-2">GWA</span>
                    <span class="font-bold text-xl" style="color: #1A5C38">{{ number_format($progress['gwa'], 4) }}</span>
                </div>
            @endif
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-bold text-lg" style="color: #1A5C38">{{ $progress['total_earned'] }}</span>
                @if (($progress['unmatched_units'] ?? 0) > 0)
                    <span class="text-xs text-gray-400 italic">(+ {{ $progress['unmatched_units'] }} unspecified)</span>
                @endif
                <span class="text-gray-400">/</span>
                <span>{{ $progress['total_required'] ?? '?' }} units</span>
            </div>
        </div>
    </div>

    @if ($progress['total_required'])
        @php
            $pct = min(100, round(($progress['total_earned'] / max(1, $progress['total_required'])) * 100));
        @endphp
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
            <div class="h-3 rounded-full transition-all duration-500"
                 style="width: {{ $pct }}%; background: linear-gradient(90deg, #1A5C38, #4CAF7D);"></div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right">{{ $pct }}% complete</p>
    @endif

    <div class="flex flex-col md:flex-row md:items-end justify-end mt-3 gap-4">

        @if ($progress['residency'] ?? null)
            @php
                $res = $progress['residency'];
                $max = $res['max_semesters'];
                $enrolled = $res['terms_enrolled'];
                $remaining = max(0, $max - $enrolled);
                $exceeded = $enrolled > $max;
                $resTerms = $progress['residency_terms'] ?? [];
            @endphp
            <div class="flex flex-col gap-1.5 w-full md:w-72">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Residency</span>
                    <span class="text-xs {{ $exceeded ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                        @if ($exceeded)
                            ⚠ Exceeded by {{ $enrolled - $max }} terms
                        @else
                            {{ $remaining }} remaining of {{ $max }}
                        @endif
                    </span>
                </div>
                
                {{-- Segmented Progress Bar --}}
                <div class="flex w-full h-2.5 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 gap-0.5">
                    @for ($i = 1; $i <= max($max, $enrolled); $i++)
                        @php
                            $isRemaining = $i > $enrolled;
                            $isExceeded = $i > $max;
                            $termInfo = $resTerms[$i - 1] ?? null;
                            $tooltip = $isRemaining ? "Available Slot {$i}" : ($termInfo ? "{$termInfo['term_label']} ({$termInfo['term_code']})" : "Used Semester {$i}");
                            
                            // Color logic
                            if ($isExceeded) {
                                $bg = 'bg-red-500 dark:bg-red-600';
                            } elseif (!$isRemaining) {
                                $bg = 'bg-emerald-600 dark:bg-emerald-500';
                            } else {
                                $bg = 'bg-gray-300 dark:bg-gray-600';
                            }
                        @endphp
                        <div class="flex-1 {{ $bg }} transition-opacity hover:opacity-80 cursor-help" title="{{ $tooltip }}"></div>
                    @endfor
                </div>

                {{-- Quick Term Badges --}}
                @if (!empty($resTerms))
                    <div class="flex flex-wrap items-center gap-1 mt-1">
                        @foreach ($resTerms as $rt)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-mono font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700"
                                  title="{{ $rt['term_label'] }}">
                                {{ $rt['term_code'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>



{{-- Per-Type Progress Cards --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
    @foreach ($progress['type_progress'] as $tp)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center"
             style="border-left: 4px solid {{ $tp['color'] }};">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ $tp['label'] }}</p>
            <p class="text-lg font-bold" style="color: {{ $tp['color'] }}">
                {{ $tp['earned_units'] }}<span class="text-xs font-normal text-gray-400">{{ $tp['required_units'] ? ' / ' . $tp['required_units'] : '' }} units</span>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $tp['completed_courses'] }} {{ Str::plural('course', $tp['completed_courses']) }} completed
            </p>
        </div>
    @endforeach
</div>

{{-- ═══ Courses Taken ═══ --}}
@if (!empty($progress['courses_taken_by_type']))
    <div class="mb-6">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
            <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-gray-400" />
            Courses Taken
        </h4>
        <div class="space-y-4">
            @foreach ($progress['courses_taken_by_type'] as $type => $courses)
                @php
                    $isSubGrouped = is_array($courses) && !isset($courses[0]) && !empty($courses);
                @endphp
                @if ($isSubGrouped)
                    @foreach ($courses as $subGroup => $subCourses)
                        @php
                            $typeColor = $subCourses[0]['type_color'] ?? '#999';
                            $subLabel = $subGroup === '__ungrouped__' ? 'Other Major' : $subGroup;
                        @endphp
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-3 h-3 rounded-full" style="background: {{ $typeColor }}"></div>
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ $type }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">— {{ $subLabel }}</span>
                            </div>
                            @include('filament.pages.partials._courses-taken-table', ['courses' => $subCourses])
                        </div>
                    @endforeach
                @else
                    @php
                        $typeColor = $courses[0]['type_color'] ?? '#999';
                        $completedCount = collect($courses)->where('status', 'completed')->count();
                    @endphp
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-3 h-3 rounded-full" style="background: {{ $typeColor }}"></div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ $type }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">— {{ $completedCount }} {{ Str::plural('course', $completedCount) }} completed</span>
                        </div>
                        @include('filament.pages.partials._courses-taken-table', ['courses' => $courses])
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif

{{-- Thesis/Dissertation/Field Study Enrollment Timeline --}}
@if (!empty($progress['thesis_enrollments']))
    <div class="rounded-lg border border-indigo-200 dark:border-indigo-700/50 bg-indigo-50/30 dark:bg-indigo-900/10 p-4 mb-6">
        <h4 class="text-sm font-semibold text-indigo-700 dark:text-indigo-400 mb-3 flex items-center gap-2">
            <x-heroicon-o-document-text class="w-4 h-4" />
            Thesis / Dissertation / Field Study Enrollment
        </h4>
        <div class="space-y-3">
            @foreach ($progress['thesis_enrollments'] as $te)
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ $te['course_code'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $te['course_name'] }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium ml-auto
                            {{ match($te['latest_status']) {
                                'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'enrolled', 'in-progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                            } }}">
                            {{ ucfirst(str_replace('-', ' ', $te['latest_status'] ?? 'unknown')) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs text-gray-400 dark:text-gray-500 mr-1">Enrolled:</span>
                        @foreach ($te['terms'] as $term)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-medium border
                                {{ match($term['status']) {
                                    'completed' => 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700',
                                    'enrolled', 'in-progress' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-700',
                                    default => 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600',
                                } }}"
                                  title="{{ $term['term_label'] }} — {{ ucfirst($term['status']) }}">
                                {{ $term['term_code'] }}
                            </span>
                        @endforeach
                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">← {{ $te['total_terms'] }} {{ Str::plural('term', $te['total_terms']) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══ Curriculum Reference (collapsed) ═══ --}}
@if (!empty($progress['curriculum_inline']) || !empty($progress['curriculum_collapsible']))
    @php
        $allCurriculum = array_merge($progress['curriculum_inline'] ?? [], $progress['curriculum_collapsible'] ?? []);
        $typeOrder = ['Core', 'Prescribed', 'Major', 'Specialization', 'Elective', 'Cognate', 'Seminar', 'Dissertation', 'Thesis', 'Field Study'];
        uksort($allCurriculum, function ($a, $b) use ($typeOrder) {
            $posA = array_search($a, $typeOrder);
            $posB = array_search($b, $typeOrder);
            return ($posA === false ? 99 : $posA) - ($posB === false ? 99 : $posB);
        });
    @endphp
    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700" x-data="{ showRef: false }">
        <button @click="showRef = !showRef" class="flex items-center gap-2 w-full text-left group">
            <x-heroicon-o-clipboard-document-list class="w-4 h-4 text-gray-400" />
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Curriculum Reference</span>
            <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 transition-transform ml-auto" x-bind:class="{ 'rotate-180': showRef }" />
        </button>
        <div x-show="showRef" x-collapse class="mt-3">
            @foreach ($allCurriculum as $type => $courses)
                @php $isSubGrouped = (in_array($type, ['Major', 'Specialization']) && is_array($courses) && !isset($courses[0])); @endphp
                <div class="mb-4" x-data="{ open: false }">
                    @if ($isSubGrouped)
                        @php $totalCount = collect($courses)->flatten(1)->count(); $firstCourse = collect($courses)->flatten(1)->first(); @endphp
                        <button @click="open = !open" class="flex items-center gap-2 mb-2 w-full text-left group">
                            <div class="w-3 h-3 rounded-full" style="background: {{ $firstCourse['color'] ?? '#999' }}"></div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ $type }}</span>
                            <span class="text-xs text-gray-400">({{ $totalCount }} courses)</span>
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 transition-transform ml-auto" x-bind:class="{ 'rotate-180': open }" />
                        </button>
                        <div x-show="open" x-collapse>
                            @foreach ($courses as $subGroup => $subCourses)
                                <div class="mb-3 pl-5">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 border-b border-gray-100 dark:border-gray-700 pb-1">{{ $subGroup }}</p>
                                    <div class="space-y-1">
                                        @foreach ($subCourses as $course)
                                            @include('filament.pages.partials._curriculum-row', ['course' => $course])
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif (is_array($courses) && count($courses) > 5)
                        <button @click="open = !open" class="flex items-center gap-2 mb-2 w-full text-left group">
                            <div class="w-3 h-3 rounded-full" style="background: {{ $courses[0]['color'] ?? '#999' }}"></div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ $type }}</span>
                            <span class="text-xs text-gray-400">({{ count($courses) }} courses)</span>
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400 transition-transform ml-auto" x-bind:class="{ 'rotate-180': open }" />
                        </button>
                        <div x-show="open" x-collapse class="space-y-1 pl-5">
                            @foreach ($courses as $course)
                                @include('filament.pages.partials._curriculum-row', ['course' => $course])
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-3 h-3 rounded-full" style="background: {{ $courses[0]['color'] ?? '#999' }}"></div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ $type }}</span>
                        </div>
                        <div class="space-y-1 pl-5">
                            @foreach ($courses as $course)
                                @include('filament.pages.partials._curriculum-row', ['course' => $course])
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══ Unspecified Courses ═══ --}}
@if (!empty($progress['unmatched_courses']))
    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-400" />
            Unspecified Courses
            @if (($progress['unmatched_units'] ?? 0) > 0)
                <span class="text-xs text-gray-400 font-normal">({{ $progress['unmatched_units'] }} units)</span>
            @endif
        </h4>
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">
            Courses in enrollment records but not in this program's curriculum.
        </p>
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50 text-left">
                        <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Course</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-16 text-center">Units</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-20 text-center">Term</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-48 text-center">Classify As</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-16 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($progress['unmatched_courses'] as $uc)
                        <tr wire:key="unmatched-{{ $uc['enrollment_id'] }}">
                            <td class="px-3 py-2">
                                <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $uc['course_code'] }}</span>
                                @if ($uc['course_name'] ?? null)
                                    <span class="text-gray-500 dark:text-gray-500 ml-1">{{ $uc['course_name'] }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center text-gray-500">{{ $uc['units'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-center font-mono text-xs text-gray-400">{{ $uc['term_taken'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-center">
                                <select wire:change="classifyUnmatchedCourse({{ $uc['enrollment_id'] }}, $event.target.value)"
                                    class="text-xs rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-1 px-2 text-gray-600 dark:text-gray-400 w-full">
                                    <option value="">— Select —</option>
                                    @foreach (['core','prescribed','major','specialization','elective','cognate','seminar','thesis','dissertation','field_study'] as $opt)
                                        <option value="{{ $opt }}">{{ ucfirst(str_replace('_', ' ', $opt)) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button wire:click="dismissUnmatchedCourse({{ $uc['enrollment_id'] }})"
                                    wire:confirm="Remove this course from records?"
                                    class="text-gray-400 hover:text-red-500 transition-colors p-1" title="Dismiss">
                                    <x-heroicon-o-x-mark class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
