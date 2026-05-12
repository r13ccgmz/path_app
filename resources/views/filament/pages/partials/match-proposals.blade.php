<div class="space-y-4">
    {{-- Header with title and action buttons --}}
    <div class="flex flex-wrap justify-between items-center gap-3">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Proposed Matches ({{ count($proposals) }})</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Review and check the matches you want to commit</p>
        </div>
        <div class="flex gap-2">
            <x-filament::button wire:click="toggleAll" color="gray" size="sm">
                Toggle All
            </x-filament::button>
            <x-filament::button wire:click="cancelReview" color="gray" size="sm">
                Cancel
            </x-filament::button>
            <x-filament::button wire:click="commitSelected" color="success" size="sm" icon="heroicon-o-check">
                Commit Selected
            </x-filament::button>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Table header --}}
        <div class="hidden md:grid grid-cols-12 gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-900 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            <div class="col-span-1"></div>
            <div class="col-span-3">Graduate</div>
            <div class="col-span-2">Grad. Program</div>
            <div class="col-span-3">Enrollee Match</div>
            <div class="col-span-2">Enrollee Program</div>
            <div class="col-span-1">Similarity</div>
        </div>

        {{-- Rows --}}
        <div class="max-h-[50vh] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($proposals as $i => $p)
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 px-4 py-3 items-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ !($selected[$i] ?? false) ? 'opacity-40' : '' }}">
                    <div class="col-span-1">
                        <input type="checkbox" wire:model.live="selected.{{ $i }}"
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                    </div>
                    <div class="col-span-3">
                        <p class="font-medium text-sm text-gray-900 dark:text-white">{{ $p['graduate_name'] }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $p['graduate_program'] ?: '—' }}</p>
                    </div>
                    <div class="col-span-3">
                        <p class="font-medium text-sm text-blue-600 dark:text-blue-400">{{ $p['enrollee_name'] }}</p>
                        <p class="text-xs text-gray-400">{{ \App\Models\Enrollee::formatStudentNumber($p['student_number']) }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $p['enrollee_program'] }}</p>
                    </div>
                    <div class="col-span-1">
                        @php
                            $color = $p['similarity'] >= 93 ? 'success' : ($p['similarity'] >= 85 ? 'warning' : 'danger');
                        @endphp
                        <x-filament::badge :color="$color">{{ $p['similarity'] }}%</x-filament::badge>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
