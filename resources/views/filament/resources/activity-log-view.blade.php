<div class="space-y-4">
    {{-- Meta --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">User</span>
            <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                {{ $record->causer?->name ?? 'System' }}
                @if($record->causer?->email)
                    <span class="text-xs text-gray-500">({{ $record->causer->email }})</span>
                @endif
            </p>
        </div>
        <div>
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Timestamp</span>
            <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                {{ $record->created_at->format('M d, Y \a\t H:i:s') }}
                <span class="text-xs text-gray-500">({{ $record->created_at->diffForHumans() }})</span>
            </p>
        </div>
        <div>
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Record Type</span>
            <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                {{ $record->subject_type ? class_basename($record->subject_type) : '—' }} #{{ $record->subject_id ?? '—' }}
            </p>
        </div>
        <div>
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Action</span>
            <p class="text-sm font-medium mt-1">
                <span @class([
                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => $record->description === 'created',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' => $record->description === 'updated',
                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => $record->description === 'deleted',
                    'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300' => !in_array($record->description, ['created', 'updated', 'deleted']),
                ])>
                    {{ ucfirst($record->description) }}
                </span>
            </p>
        </div>
    </div>

    {{-- Deleted Record Identity --}}
    @if(str_contains($record->description ?? '', 'deleted'))
        @php
            $deletedData = $record->properties->get('old', []);
            $identifiers = array_filter([
                'full_name' => $deletedData['full_name'] ?? null,
                'student_number' => $deletedData['student_number'] ?? null,
                'email' => $deletedData['email'] ?? null,
                'name' => $deletedData['name'] ?? null,
                'code' => $deletedData['code'] ?? null,
                'course_code' => $deletedData['course_code'] ?? null,
            ]);
        @endphp
        @if(!empty($identifiers))
            <div class="rounded-lg bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/50 px-4 py-3">
                <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase mb-1">Deleted Record Details</p>
                <div class="flex flex-wrap gap-3">
                    @foreach($identifiers as $key => $val)
                        <div class="text-sm">
                            <span class="text-red-500 dark:text-red-400 font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                            <span class="text-red-700 dark:text-red-300 font-semibold">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- Changes --}}
    @php
        $old = $record->properties->get('old', []);
        $new = $record->properties->get('attributes', []);
        $isDeleteEvent = str_contains($record->description ?? '', 'deleted');
        $isCreateEvent = str_contains($record->description ?? '', 'created');
    @endphp

    @if($isDeleteEvent && !empty($old))
        {{-- For delete events, show all the old values that were lost --}}
        <div class="border-t border-gray-200 dark:border-white/10 pt-4">
            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3">Record Data at Time of Deletion</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="text-left py-2 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Field</th>
                            <th class="text-left py-2 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($old as $key => $value)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 px-3 font-medium text-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                                <td class="py-2 px-3 text-red-600 dark:text-red-400">
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @elseif(is_null($value))
                                        <span class="text-gray-400 italic">null</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($isCreateEvent && !empty($new))
        {{-- For create events, show the new values --}}
        <div class="border-t border-gray-200 dark:border-white/10 pt-4">
            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3">Created Record Data</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="text-left py-2 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Field</th>
                            <th class="text-left py-2 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($new as $key => $value)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 px-3 font-medium text-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                                <td class="py-2 px-3 text-green-600 dark:text-green-400">
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @elseif(is_null($value))
                                        <span class="text-gray-400 italic">null</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(!empty($old) || !empty($new))
        {{-- For update events, show old vs new comparison --}}
        <div class="border-t border-gray-200 dark:border-white/10 pt-4">
            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3">Changes</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="text-left py-2 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Field</th>
                            <th class="text-left py-2 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Old Value</th>
                            <th class="text-left py-2 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">New Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($new as $key => $value)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 px-3 font-medium text-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                                <td class="py-2 px-3 text-red-600 dark:text-red-400">
                                    @if(is_array($old[$key] ?? null))
                                        {{ json_encode($old[$key]) }}
                                    @else
                                        {{ $old[$key] ?? '—' }}
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-green-600 dark:text-green-400">
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @else
                                        {{ $value ?? '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="border-t border-gray-200 dark:border-white/10 pt-4 text-center text-sm text-gray-500 dark:text-gray-400">
            No change details recorded for this event.
        </div>
    @endif
</div>
