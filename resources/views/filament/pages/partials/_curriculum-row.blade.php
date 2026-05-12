{{-- Curriculum row partial - receives $course array --}}
<div class="flex items-center gap-3 py-1 px-3 rounded-md
    {{ $course['is_completed'] ? 'bg-green-50 dark:bg-green-900/20' : ($course['is_taken'] ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800/50') }}">
    @if ($course['is_completed'])
        <x-heroicon-s-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" />
    @elseif ($course['is_taken'])
        <x-heroicon-s-clock class="w-4 h-4 text-yellow-500 flex-shrink-0" />
    @else
        <x-heroicon-o-minus-circle class="w-4 h-4 text-gray-300 dark:text-gray-600 flex-shrink-0" />
    @endif
    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $course['course_code'] }}</span>
    <span class="text-sm text-gray-500 dark:text-gray-400 flex-1">{{ $course['course_name'] }}</span>
    <span class="text-xs text-gray-400">{{ $course['units'] ?? '-' }} units</span>
    @if ($course['term_taken'])
        <span class="text-xs font-mono text-gray-400">{{ $course['term_taken'] }}</span>
    @elseif (!$course['is_taken'])
        <span class="text-xs text-gray-400 italic">not yet taken</span>
    @endif
</div>
