<x-filament-panels::page>

    {{-- ═══ Faculty Workload Table ═══ --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
            <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
            Faculty Workload
        </h2>
        {{ $this->table }}
    </div>

    {{-- ═══ External / Panel Members Directory ═══ --}}
    @if($externalMembers->count() > 0 || !empty($this->externalSearch))
    <div class="mt-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                <x-heroicon-o-user-plus class="w-5 h-5" />
                External / Panel Members Directory
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $externalMembers->count() }} members)</span>
            </h2>
            <div class="w-64">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="externalSearch"
                    placeholder="Search external members..."
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Designation</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($externalMembers as $member)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-gray-100">
                            {{ $member->full_name }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">
                            {{ $member->designation ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">
                            {{ $member->email ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-right space-x-1">
                            <button
                                wire:click="editExternal({{ $member->id }})"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20 transition-colors"
                            >
                                <x-heroicon-m-pencil-square class="w-3.5 h-3.5" />
                                Edit
                            </button>
                            <button
                                wire:click="deleteExternal({{ $member->id }})"
                                wire:confirm="Are you sure you want to delete {{ $member->full_name }}?"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium text-danger-600 hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-900/20 transition-colors"
                            >
                                <x-heroicon-m-trash class="w-3.5 h-3.5" />
                                Delete
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Required for Filament page-level actions (edit modal) --}}
    <x-filament-actions::modals />

</x-filament-panels::page>
