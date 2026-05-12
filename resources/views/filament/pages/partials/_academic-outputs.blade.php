{{-- Academic Output Section --}}
@if ($this->getStudentRecord())
    @php
        $academicOutputs = $this->getAcademicOutputs();
    @endphp
    <x-filament::section heading="Academic Output" icon="heroicon-o-document-text" collapsible collapsed>
        @if (count($academicOutputs) > 0)
            <div class="space-y-3 mb-4">
                @foreach ($academicOutputs as $ao)
                    @if ($deletingAoId === $ao['id'])
                        {{-- Delete Confirmation --}}
                        <div wire:key="ao-delete-{{ $ao['id'] }}" class="rounded-lg border border-red-300 dark:border-red-700 bg-red-50/50 dark:bg-red-900/10 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-red-600 dark:text-red-400">
                                    Delete <strong>{{ $ao['title'] }}</strong>? This cannot be undone.
                                </span>
                                <div class="flex gap-2">
                                    <x-filament::button wire:click="deleteAo" color="danger" size="sm" icon="heroicon-o-trash">
                                        Confirm Delete
                                    </x-filament::button>
                                    <x-filament::button wire:click="cancelDeleteAo" color="gray" size="sm">
                                        Cancel
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div wire:key="ao-row-{{ $ao['id'] }}" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h5 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $ao['title'] ?? 'Untitled' }}</h5>
                                    <div class="flex flex-col gap-2 mb-4">
                                        @if(!empty($ao['primary_authors']))
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-900/30 dark:text-emerald-400">Primary Author(s)</span>
                                            <span class="text-xs text-gray-500">{{ implode('; ', $ao['primary_authors']) }}</span>
                                        </div>
                                        @endif

                                        @if(!empty($ao['co_authors']))
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400">Co-Author(s)</span>
                                            <span class="text-xs text-gray-500">{{ implode('; ', $ao['co_authors']) }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                        <div>
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Type</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $ao['type_label'] ?? ucfirst(str_replace('-', ' ', $ao['type'])) }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst(str_replace('-', ' ', $ao['status'])) }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Semester / Term</span>
                                            @if(!empty($ao['term_code']))
                                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400">[{{ $ao['term_code'] }}]{{ !empty($ao['semester_label']) ? ' ' . $ao['semester_label'] : '' }}</span>
                                            @else
                                                <span class="font-medium text-gray-400 dark:text-gray-500">—</span>
                                            @endif
                                        </div>

                                        @if ($ao['proposal_defense_date'])
                                        <div>
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Proposal Defense</span>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $ao['proposal_defense_date'] }}</span>
                                                @if(!empty($ao['proposal_defense_result']))
                                                    @php
                                                        $resStr = strtolower(trim($ao['proposal_defense_result']));
                                                        $badgeClass = 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-500/20';
                                                        if (str_contains($resStr, 'revision') || str_contains($resStr, 'conditional')) {
                                                            $badgeClass = 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-900/30 dark:text-warning-400 dark:ring-warning-500/20';
                                                        } elseif (str_contains($resStr, 'pass')) {
                                                            $badgeClass = 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-900/30 dark:text-success-400 dark:ring-success-500/20';
                                                        } elseif (str_contains($resStr, 'fail')) {
                                                            $badgeClass = 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-400 dark:ring-danger-500/20';
                                                        }
                                                    @endphp
                                                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badgeClass }}">
                                                        {{ ucfirst(str_replace('-', ' ', $ao['proposal_defense_result'])) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        
                                        @if ($ao['final_defense_date'])
                                        <div>
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Final Defense</span>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $ao['final_defense_date'] }}</span>
                                                @if(!empty($ao['final_defense_result']))
                                                    @php
                                                        $resStr = strtolower(trim($ao['final_defense_result']));
                                                        $badgeClass = 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-500/20';
                                                        if (str_contains($resStr, 'revision') || str_contains($resStr, 'conditional')) {
                                                            $badgeClass = 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-900/30 dark:text-warning-400 dark:ring-warning-500/20';
                                                        } elseif (str_contains($resStr, 'pass')) {
                                                            $badgeClass = 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-900/30 dark:text-success-400 dark:ring-success-500/20';
                                                        } elseif (str_contains($resStr, 'fail')) {
                                                            $badgeClass = 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-400 dark:ring-danger-500/20';
                                                        }
                                                    @endphp
                                                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badgeClass }}">
                                                        {{ ucfirst(str_replace('-', ' ', $ao['final_defense_result'])) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                        
                                        @if ($ao['date_submitted'])
                                        <div>
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Date Submitted</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $ao['date_submitted'] }}</span>
                                        </div>
                                        @endif
                                        
                                        @if ($ao['drive_link'] ?? null)
                                        <div class="md:col-span-2">
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Academic Output Link</span>
                                            <a href="{{ $ao['drive_link'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 break-all flex items-center gap-1">
                                                <x-heroicon-o-link class="w-4 h-4 shrink-0" />
                                                {{ $ao['drive_link'] }}
                                            </a>
                                        </div>
                                        @endif
                                    </div>

                                    @if (!empty($ao['abstract']))
                                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Abstract</span>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $ao['abstract'] }}</p>
                                        </div>
                                    @endif

                                    @if (!empty($ao['keywords']))
                                        <div class="mt-3 {{ empty($ao['abstract']) ? 'pt-3 border-t border-gray-100 dark:border-gray-700' : '' }}">
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Keywords</span>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ((is_array($ao['keywords']) ? $ao['keywords'] : explode(',', $ao['keywords'])) as $keyword)
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-300/50 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-600/50">{{ trim($keyword) }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if (!empty($ao['committee']))
                                        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Advisory Committee</span>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                                                @foreach ($ao['committee'] as $cm)
                                                    <div class="flex items-baseline">
                                                        <span class="w-24 text-sm text-gray-500 dark:text-gray-400">{{ $cm['role_label'] }}:</span>
                                                        <div class="flex flex-col">
                                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $cm['name'] }}{{ !empty($cm['designation']) ? ' (' . $cm['designation'] . ')' : '' }}</span>
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
                                </div>
                                @if ($ao['is_editable'] ?? true)
                                <div class="flex gap-1 ml-3 mt-1">
                                    <button wire:click="startEditAo({{ $ao['id'] }})" class="text-gray-400 hover:text-primary-600 transition-colors p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Edit">
                                        <x-heroicon-o-pencil class="w-4 h-4" />
                                    </button>
                                    <button wire:click="confirmDeleteAo({{ $ao['id'] }})" class="text-gray-400 hover:text-red-600 transition-colors p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Delete">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Add Academic Output (via Filament Action modal) --}}
        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
            {{ $this->getAction('addAcademicOutput') }}
        </div>
    </x-filament::section>
@endif
