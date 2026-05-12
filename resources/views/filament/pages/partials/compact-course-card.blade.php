<div class="compact-card group" wire:key="course-card-{{ $course['mapping_id'] }}" style="--card-accent: {{ $phaseColor }}; --card-accent-light: {{ $phaseColorLight }};">
    {{-- Top row: Code pill + Units + Actions --}}
    <div class="compact-card-header">
        <span class="compact-code-pill" style="background-color: {{ $phaseColor }}20; color: {{ $phaseColor }};">
            {{ $course['code'] }}
        </span>
        <div class="flex items-center gap-1.5" x-data="{ editingUnits: false, unitsValue: '{{ $course['units'] ?? '' }}' }">
            <template x-if="!editingUnits">
                <span @click="editingUnits = true" class="compact-units cursor-pointer hover:bg-black/5 dark:hover:bg-white/10 px-1 py-0.5 rounded transition-colors" title="Click to manually override course units">
                    {{ $course['units'] ? $course['units'] . ' ' . Str::plural('unit', $course['units']) : 'Set Units' }}
                </span>
            </template>
            <template x-if="editingUnits">
                <span class="flex items-center gap-1">
                    <input type="number" min="0" max="99" x-model.number="unitsValue" 
                        class="w-12 h-5 text-xs text-center border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-1 py-0"
                        @keydown.enter="editingUnits = false; $wire.updateCourseMappingUnits({{ $course['mapping_id'] }}, unitsValue)"
                        @keydown.escape="editingUnits = false; unitsValue = '{{ $course['units'] ?? '' }}'"
                        @blur="editingUnits = false; $wire.updateCourseMappingUnits({{ $course['mapping_id'] }}, unitsValue)"
                        x-init="$nextTick(() => $el.focus())"
                    />
                </span>
            </template>
            {{-- Hover actions --}}
            <div class="compact-actions">
                <a href="{{ $this->getEditCourseUrl($course['course_id']) }}" title="Edit">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                    </svg>
                </a>
                <button wire:click="removeCourseMapping({{ $course['mapping_id'] }})" wire:confirm="Remove this course from the program curriculum?" title="Remove">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Course name --}}
    <div class="compact-card-name">
        {{ $course['name'] }}
        @if(str_contains($course['code'], '/'))
            <span class="text-xs font-normal opacity-50 italic ml-1">(Choose one)</span>
        @endif
    </div>

    {{-- Tags row: prerequisite, semester, cognate --}}
    <div class="compact-card-tags">
        @if ($course['prerequisite'])
            <span class="compact-tag prereq">
                <svg class="h-2.5 w-2.5 opacity-70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                {{ $course['prerequisite'] }}
            </span>
        @endif

        @if ($course['semester'])
            <span class="compact-tag semester">
                <svg class="h-2.5 w-2.5 opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                {{ $course['semester'] }}
            </span>
        @endif

        @if ($course['cognate_field'])
            <span class="compact-tag cognate">
                {{ $course['cognate_field'] }}
            </span>
        @endif

        @if (!empty($course['ms_conditional']))
            <span class="compact-tag ms-conditional">
                <svg class="h-2.5 w-2.5 opacity-70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                MS Conditional
            </span>
        @endif
    </div>

    {{-- Notes (if any) --}}
    @if (!empty($course['notes']))
        <div class="compact-card-note">
            <svg class="h-2.5 w-2.5 shrink-0 mt-px opacity-50" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
            </svg>
            <span>{{ $course['notes'] }}</span>
        </div>
    @endif
</div>
