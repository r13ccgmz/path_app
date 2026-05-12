{{-- Graduation Info — one section per graduation record --}}
@if (empty($studentInfo['is_graduate']) && $this->getStudentRecord())
    <div class="mb-6 bg-white dark:bg-gray-900 border border-success-200 dark:border-success-900/50 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between shadow-sm gap-4">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-success-50 dark:bg-success-900/30 rounded-lg">
                <x-heroicon-o-academic-cap class="w-6 h-6 text-success-600 dark:text-success-400" />
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Graduation Status</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">This student does not have graduation information recorded yet.</p>
            </div>
        </div>
        <x-filament::button wire:click="mountAction('addGraduateInfo')" color="success" icon="heroicon-o-check-badge" size="md" class="w-full sm:w-auto shrink-0">
            Add Graduation Info
        </x-filament::button>
    </div>
@endif

@if (!empty($studentInfo['graduation_records']))
    @foreach ($studentInfo['graduation_records'] as $gradRecord)
    <x-filament::section icon="heroicon-o-check-badge" icon-color="success" class="!bg-emerald-50/50 dark:!bg-emerald-900/20 !border-emerald-200 dark:!border-emerald-800" collapsible>
        <x-slot name="heading">Graduation Info{{ !empty($gradRecord['program_name']) ? ' — ' . $gradRecord['program_name'] : (count($studentInfo['graduation_records']) > 1 ? ' — ' . ($gradRecord['degree'] ?? '') : '') }}</x-slot>
        <x-slot name="afterHeader">
            <div class="flex items-center gap-2">
                <x-filament::button wire:click="startEditGraduation({{ $gradRecord['id'] }})" color="warning" icon="heroicon-o-pencil-square" size="xs">Edit</x-filament::button>
                <x-filament::button wire:click="startDeleteGraduation({{ $gradRecord['id'] }})" color="danger" icon="heroicon-o-trash" size="xs">Remove</x-filament::button>
            </div>
        </x-slot>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-12 gap-y-6">
            <div>
                <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Semester Graduated</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ empty($gradRecord['semester_graduated']) ? '-' : $gradRecord['semester_graduated'] }}</span>
            </div>
            <div>
                <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Degree</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ empty($gradRecord['degree']) ? '-' : $gradRecord['degree'] }}</span>
            </div>
            <div>
                <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Country of Origin</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ empty($gradRecord['country_of_origin']) ? '-' : $gradRecord['country_of_origin'] }}</span>
            </div>
            <div class="md:col-span-2">
                <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Program</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ empty($gradRecord['program_name']) ? '-' : $gradRecord['program_name'] }}</span>
            </div>
            @if (!empty($gradRecord['major']))
            <div>
                <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Specialization</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $gradRecord['major'] }}</span>
            </div>
            @endif
        </div>

        @if (!empty($gradRecord['committee_data']))
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Graduation Committee</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                @foreach ($gradRecord['committee_data'] as $cm)
                    <div class="flex items-baseline">
                        <span class="w-28 text-sm text-gray-500 dark:text-gray-400">{{ $cm['role'] ?? '-' }}:</span>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $cm['name'] ?? '-' }}{{ !empty($cm['designation']) ? ' (' . $cm['designation'] . ')' : '' }}</span>
                            @if(!empty($cm['formatted_appointed_date']))
                                <span class="text-xs text-gray-400 dark:text-gray-500">Appointed: {{ $cm['formatted_appointed_date'] }}</span>
                            @endif
                            @if(!empty($cm['term_start']) || !empty($cm['term_end']))
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    Term: {{ $cm['term_start'] ?? '—' }} to {{ $cm['term_end'] ?? 'Present' }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
        </x-filament::section>
    @endforeach
@endif
