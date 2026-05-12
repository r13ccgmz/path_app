{{-- Courses Taken Table partial - receives $courses array --}}
<div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 dark:bg-gray-800/50 text-left">
                <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-8">Status</th>
                <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Course</th>
                <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-32 text-center">Type</th>
                <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-16 text-center">Units</th>
                <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-24 text-center">Grade</th>
                <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-44 text-center">Term</th>
                <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-28 text-center">Status</th>
                <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-8"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($courses as $course)
                @if (($deletingEnrollmentId ?? null) === $course['enrollment_id'])
                    {{-- Delete confirmation row --}}
                    <tr wire:key="delete-confirm-{{ $course['enrollment_id'] }}" class="bg-red-50/50 dark:bg-red-900/10">
                        <td colspan="8" class="px-3 py-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-red-600 dark:text-red-400">
                                    Delete <strong>{{ $course['course_code'] }}</strong> enrollment?
                                </span>
                                <div class="flex gap-2">
                                    <x-filament::button wire:click="deleteEnrollment" color="danger" size="sm" icon="heroicon-o-trash">
                                        Confirm
                                    </x-filament::button>
                                    <x-filament::button wire:click="cancelDeleteEnrollment" color="gray" size="sm">
                                        Cancel
                                    </x-filament::button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @else
                    <tr wire:key="course-{{ $course['enrollment_id'] }}" class="{{ match($course['status']) {
                        'completed' => 'bg-green-50/50 dark:bg-green-900/10',
                        'enrolled', 'in-progress' => 'bg-blue-50/50 dark:bg-blue-900/10',
                        'incomplete' => 'bg-yellow-50/50 dark:bg-yellow-900/10',
                        'dropped', 'withdrawn' => 'bg-red-50/30 dark:bg-red-900/10',
                        default => '',
                    } }}">
                        <td class="px-3 py-2 text-center">
                            @if ($course['status'] === 'completed')
                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mx-auto" />
                            @elseif (in_array($course['status'], ['enrolled', 'in-progress']))
                                <x-heroicon-s-clock class="w-5 h-5 text-blue-500 mx-auto" />
                            @elseif ($course['status'] === 'incomplete')
                                <x-heroicon-s-exclamation-circle class="w-5 h-5 text-yellow-500 mx-auto" />
                            @elseif (in_array($course['status'], ['dropped', 'withdrawn']))
                                <x-heroicon-s-x-circle class="w-5 h-5 text-red-400 mx-auto" />
                            @else
                                <x-heroicon-o-clock class="w-5 h-5 text-gray-400 mx-auto" />
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $course['course_code'] }}</span>
                                @if (($course['source'] ?? 'imported') === 'manual')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                        Manual Entry
                                    </span>
                                @endif
                            </div>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">{{ $course['course_name'] }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <select
                                wire:change="updateEnrollmentField({{ $course['enrollment_id'] }}, 'course_type_override', $event.target.value)"
                                class="w-full text-xs rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-1 px-1 text-gray-600 dark:text-gray-400"
                                title="Override course type for this student"
                            >
                                <option value="" {{ empty($course['course_type_override'] ?? null) ? 'selected' : '' }}>{{ $course['type'] }}</option>
                                @foreach (['core','prescribed','major','specialization','elective','cognate','seminar','thesis','dissertation','field_study'] as $opt)
                                    @php $optLabel = ucfirst(str_replace('_', ' ', $opt)); @endphp
                                    @if ($optLabel !== $course['type'])
                                        <option value="{{ $opt }}" {{ ($course['course_type_override'] ?? '') === $opt ? 'selected' : '' }}>{{ $optLabel }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </td>
                        <td class="px-3 py-2 text-center font-mono text-xs text-gray-500 dark:text-gray-400">
                            <input type="number"
                                wire:change="updateEnrollmentField({{ $course['enrollment_id'] }}, 'units_earned', $event.target.value)"
                                class="w-12 text-center text-xs rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-1 px-1 text-gray-600 dark:text-gray-400 focus:ring-1 focus:ring-primary-500"
                                value="{{ $course['units'] ?? '' }}"
                                placeholder="-"
                                min="0" max="99"
                            >
                        </td>
                        <td class="px-3 py-2 text-center">
                            <select
                                wire:change="updateEnrollmentField({{ $course['enrollment_id'] }}, 'grade', $event.target.value)"
                                class="w-20 text-center text-xs rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-1 px-1 text-gray-600 dark:text-gray-400"
                            >
                                <option value="" {{ empty($course['grade']) ? 'selected' : '' }}>-</option>
                                <option value="1.0" {{ ($course['grade'] ?? '') === '1.0' ? 'selected' : '' }}>1.0</option>
                                <option value="1.25" {{ ($course['grade'] ?? '') === '1.25' ? 'selected' : '' }}>1.25</option>
                                <option value="1.5" {{ ($course['grade'] ?? '') === '1.5' ? 'selected' : '' }}>1.5</option>
                                <option value="1.75" {{ ($course['grade'] ?? '') === '1.75' ? 'selected' : '' }}>1.75</option>
                                <option value="2.0" {{ ($course['grade'] ?? '') === '2.0' ? 'selected' : '' }}>2.0</option>
                                <option value="2.25" {{ ($course['grade'] ?? '') === '2.25' ? 'selected' : '' }}>2.25</option>
                                <option value="2.5" {{ ($course['grade'] ?? '') === '2.5' ? 'selected' : '' }}>2.5</option>
                                <option value="2.75" {{ ($course['grade'] ?? '') === '2.75' ? 'selected' : '' }}>2.75</option>
                                <option value="3.0" {{ ($course['grade'] ?? '') === '3.0' ? 'selected' : '' }}>3.0</option>
                                <option value="4.0" {{ ($course['grade'] ?? '') === '4.0' ? 'selected' : '' }}>4.0</option>
                                <option value="5.0" {{ ($course['grade'] ?? '') === '5.0' ? 'selected' : '' }}>5.0</option>
                                <option value="INC" {{ ($course['grade'] ?? '') === 'INC' ? 'selected' : '' }}>INC</option>
                                <option value="DRP" {{ ($course['grade'] ?? '') === 'DRP' ? 'selected' : '' }}>DRP</option>
                            </select>
                        </td>
                        {{-- Term: editable code input + label --}}
                        <td class="px-3 py-1.5 text-center">
                            <div class="flex flex-col items-center gap-0.5">
                                <input type="text"
                                    wire:change="updateEnrollmentField({{ $course['enrollment_id'] }}, 'term', $event.target.value)"
                                    class="w-14 text-center text-xs font-mono rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-0.5 px-1 text-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-primary-500"
                                    value="{{ $course['term_code_raw'] ?? '' }}"
                                    placeholder="-"
                                >
                                @if ($course['term_taken'] && $course['term_taken'] !== $course['term_code_raw'])
                                    <span class="text-[10px] leading-tight text-gray-400 dark:text-gray-500">{{ $course['term_taken'] }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <select
                                wire:change="updateEnrollmentField({{ $course['enrollment_id'] }}, 'status', $event.target.value)"
                                class="text-xs rounded-md border-gray-300 dark:border-gray-600 py-1 px-2 transition-colors
                                {{ match($course['status']) {
                                    'completed' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'enrolled', 'in-progress' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    'incomplete' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    'dropped', 'withdrawn' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                } }}"
                            >
                                <option value="enrolled" {{ $course['status'] === 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                                <option value="completed" {{ $course['status'] === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="in-progress" {{ $course['status'] === 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="incomplete" {{ $course['status'] === 'incomplete' ? 'selected' : '' }}>Incomplete</option>
                                <option value="dropped" {{ $course['status'] === 'dropped' ? 'selected' : '' }}>Dropped</option>
                                <option value="withdrawn" {{ $course['status'] === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                            </select>
                        </td>
                        <td class="px-3 py-1 text-center">
                            <button wire:click="confirmDeleteEnrollment({{ $course['enrollment_id'] }})"
                                class="text-gray-300 hover:text-red-500 transition-colors p-1" title="Delete enrollment">
                                <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                            </button>
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
