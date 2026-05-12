<x-filament-panels::page>
    {{-- Scan button --}}
    @if (!$showProposals)
        <div class="mb-6">
            <x-filament::button wire:click="scanForMatches" icon="heroicon-o-bolt" color="warning" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="scanForMatches">Scan for Matches</span>
                <span wire:loading wire:target="scanForMatches">Scanning...</span>
            </x-filament::button>
        </div>
    @endif

    {{-- Proposals review list --}}
    @if ($showProposals && count($proposals) > 0)
        <div class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold">Proposed Matches ({{ count($proposals) }})</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Review and check the matches you want to commit</p>
                    </div>
                    <div class="flex gap-2">
                        <x-filament::button wire:click="toggleAll" color="gray" size="sm">
                            Toggle All
                        </x-filament::button>
                        <x-filament::button wire:click="cancelScan" color="gray" size="sm">
                            Cancel
                        </x-filament::button>
                        <x-filament::button wire:click="commitSelected" color="success" size="sm" icon="heroicon-o-check">
                            Commit Selected
                        </x-filament::button>
                    </div>
                </div>

                {{-- Table header --}}
                <div class="hidden md:grid grid-cols-12 gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                    <div class="col-span-1"></div>
                    <div class="col-span-3">Graduate</div>
                    <div class="col-span-2">Grad. Program</div>
                    <div class="col-span-3">Enrollee Match</div>
                    <div class="col-span-2">Enrollee Program</div>
                    <div class="col-span-1">Similarity</div>
                </div>

                {{-- Rows --}}
                @foreach ($proposals as $i => $p)
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 px-4 py-3 border-t border-gray-100 dark:border-gray-700 items-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ !($selected[$i] ?? false) ? 'opacity-50' : '' }}">
                        <div class="col-span-1">
                            <input type="checkbox" wire:model.live="selected.{{ $i }}"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div class="col-span-3">
                            <p class="font-medium text-sm">{{ $p['graduate_name'] }}</p>
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
    @endif

    {{-- Main table --}}
    {{ $this->table }}
</x-filament-panels::page>
