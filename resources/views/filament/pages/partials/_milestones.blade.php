{{-- Milestones Section --}}
@if ($this->getStudentRecord())
    @php
        $milestones = $this->getStudentMilestones();
    @endphp
    <x-filament::section heading="Milestones" icon="heroicon-o-flag" collapsible collapsed>
        @if ($milestones && count($milestones) > 0)
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 text-left">
                            <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Milestone</th>
                            <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-24 text-center">Category</th>
                            <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-28 text-center">Status</th>
                            <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-28 text-center">Date Completed</th>
                            <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Remarks</th>
                            @if (!auth()->user()->hasRole('viewer'))
                            <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 w-20 text-center">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($milestones as $milestone)
                            @if ($deletingMilestoneId === $milestone['id'])
                                {{-- Delete Confirmation Row --}}
                                <tr wire:key="milestone-delete-{{ $milestone['id'] }}" class="bg-red-50/50 dark:bg-red-900/10">
                                    <td colspan="6" class="px-3 py-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-red-600 dark:text-red-400">
                                                Delete <strong>{{ $milestone['name'] }}</strong>? This cannot be undone.
                                            </span>
                                            <div class="flex gap-2">
                                                <x-filament::button wire:click="deleteMilestone" color="danger" size="sm" icon="heroicon-o-trash">
                                                    Confirm Delete
                                                </x-filament::button>
                                                <x-filament::button wire:click="cancelDeleteMilestone" color="gray" size="sm">
                                                    Cancel
                                                </x-filament::button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                {{-- Normal Row --}}
                                <tr wire:key="milestone-row-{{ $milestone['id'] }}">
                                    <td class="px-3 py-2">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1">
                                                @if (auth()->user()->hasRole('viewer'))
                                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200 px-1 py-0.5">{{ $milestone['name'] }}</span>
                                                @else
                                                    <input type="text"
                                                        wire:change="updateMilestoneName({{ $milestone['id'] }}, $event.target.value)"
                                                        class="w-full text-sm font-medium text-gray-800 dark:text-gray-200 bg-transparent border-transparent hover:border-gray-300 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 rounded px-1 py-0.5 transition-colors"
                                                        value="{{ $milestone['name'] }}"
                                                    >
                                                @endif
                                                @if ($milestone['is_from_template'])
                                                    <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-primary-50 text-primary-600 ring-1 ring-inset ring-primary-500/10 dark:bg-primary-900/30 dark:text-primary-400 dark:ring-primary-500/20" title="Template milestone">
                                                        Template
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400" title="Manually added milestone">
                                                        Manual
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @php
                                            $catColors = match($milestone['category'] ?? 'other') {
                                                'academic' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                'research' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                                'administrative' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $catColors }}">
                                            {{ $milestone['category_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if (auth()->user()->hasRole('viewer'))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ match($milestone['status'] ?? '') {
                                                'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'in-progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'waived' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                'pending' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
                                            } }}">
                                                {{ $milestone['status_label'] ?: '—' }}
                                            </span>
                                        @else
                                            <x-filament::input.wrapper>
                                                <x-filament::input.select
                                                    wire:change="updateMilestoneStatus({{ $milestone['id'] }}, $event.target.value)"
                                                    class="{{ match($milestone['status'] ?? '') {
                                                            'completed' => 'text-green-700 dark:text-green-400',
                                                            'in-progress' => 'text-yellow-700 dark:text-yellow-400',
                                                            'waived' => 'text-blue-700 dark:text-blue-400',
                                                            'pending' => 'text-orange-600 dark:text-orange-400',
                                                            default => 'text-gray-400 dark:text-gray-500 italic',
                                                        } }}"
                                                >
                                                    <option value="" {{ empty($milestone['status']) || $milestone['status'] === 'not-started' ? 'selected' : '' }}>—</option>
                                                    <option value="pending" {{ ($milestone['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="in-progress" {{ ($milestone['status'] ?? '') === 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                                    <option value="completed" {{ ($milestone['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                                                    <option value="waived" {{ ($milestone['status'] ?? '') === 'waived' ? 'selected' : '' }}>Waived</option>
                                                </x-filament::input.select>
                                            </x-filament::input.wrapper>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center text-xs text-gray-500 dark:text-gray-400">
                                        @if (auth()->user()->hasRole('viewer'))
                                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $milestone['date_completed'] ?: '—' }}</span>
                                        @else
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="date"
                                                    value="{{ $milestone['date_completed'] ? \Carbon\Carbon::parse($milestone['date_completed'])->format('Y-m-d') : '' }}"
                                                    wire:change="updateMilestoneDate({{ $milestone['id'] }}, $event.target.value)"
                                                />
                                            </x-filament::input.wrapper>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if (auth()->user()->hasRole('viewer'))
                                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $milestone['remarks'] ?: '—' }}</span>
                                        @else
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="text"
                                                    value="{{ $milestone['remarks'] ?? '' }}"
                                                    wire:blur="updateMilestoneRemarks({{ $milestone['id'] }}, $event.target.value)"
                                                    placeholder="Add remarks..."
                                                />
                                            </x-filament::input.wrapper>
                                        @endif
                                    </td>
                                    @if (!auth()->user()->hasRole('viewer'))
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="confirmDeleteMilestone({{ $milestone['id'] }})" class="text-gray-400 hover:text-red-500 transition-colors p-1" title="Delete">
                                                <x-heroicon-o-trash class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">No milestones generated yet.</p>
            </div>
        @endif

        {{-- Add Milestone Toggle + Form --}}
        @if (!auth()->user()->hasRole('viewer'))
        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
            @if ($showMilestoneForm)
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Milestone Name</label>
                        <input type="text" wire:model="newMilestoneName" placeholder="Enter milestone name..."
                            class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-1.5 px-3 text-gray-700 dark:text-gray-300" />
                    </div>
                    <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Category</label>
                        <select wire:model="newMilestoneCategory"
                            class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-1.5 px-3 text-gray-700 dark:text-gray-300">
                            <option value="coursework">Coursework</option>
                            <option value="examination">Examination</option>
                            <option value="research">Research</option>
                            <option value="publication">Publication</option>
                            <option value="defense">Defense</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <x-filament::button wire:click="addAdHocMilestone" icon="heroicon-o-check" size="sm">
                        Save
                    </x-filament::button>
                    <x-filament::button wire:click="toggleMilestoneForm" color="gray" size="sm">
                        Cancel
                    </x-filament::button>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="toggleMilestoneForm" icon="heroicon-o-plus" size="sm" color="gray">
                        Add Milestone
                    </x-filament::button>
                    
                    @if (empty($milestones) || !collect($milestones)->contains('is_from_template', true))
                        <x-filament::button wire:click="loadTemplateMilestones" icon="heroicon-o-arrow-path" size="sm" color="primary" outlined>
                            Load Program Templates
                        </x-filament::button>
                    @endif
                </div>
            @endif
        </div>
        @endif
    </x-filament::section>
@endif
