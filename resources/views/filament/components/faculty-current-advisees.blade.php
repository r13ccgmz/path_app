@php
    $assignments = $faculty->committeeMemberships()
        ->with(['student.program', 'termStart', 'termEnd'])
        ->whereHas('student', fn($q) => $q->whereNull('deleted_at'))
        ->orderByRaw("FIELD(role, 'Adviser', 'Co-Adviser', 'Former Adviser', 'Chair', 'Co-Chair', 'Cognate', 'Major', 'Minor', 'Member')")
        ->get();
@endphp

@if($assignments->isNotEmpty())
<div class="mb-4">
    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Assignments ({{ $assignments->count() }})</h4>
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    <th class="px-3 py-2">Student</th>
                    <th class="px-3 py-2">Program</th>
                    <th class="px-3 py-2">Role</th>
                    <th class="px-3 py-2">Term</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($assignments as $cm)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-3 py-2 align-top">
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $cm->student->full_name }}</span>
                        <a href="/admin/list-of-students?studentNumber={{ $cm->student->student_number }}"
                           target="_blank"
                           class="text-xs text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 ml-1 underline decoration-dotted underline-offset-2 hover:decoration-solid transition-colors"
                           title="View student profile"
                        >{{ \App\Models\Enrollee::formatStudentNumber($cm->student->student_number) }}</a>
                    </td>
                    <td class="px-3 py-2 align-top text-gray-600 dark:text-gray-400">{{ $cm->student->program?->code ?? '—' }}</td>
                    
                    @if($this->editingAssignmentId === $cm->id)
                        {{-- Editing View --}}
                        <td class="px-3 py-2 align-top">
                            <div class="space-y-1 min-w-[120px]">
                                <select wire:model="editingRole" class="text-xs rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-850 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 w-full py-1 px-1.5 shadow-sm">
                                    <option value="Adviser">Adviser</option>
                                    <option value="Co-Adviser">Co-Adviser</option>
                                    <option value="Former Adviser">Former Adviser</option>
                                    <option value="Chair">Chair</option>
                                    <option value="Co-Chair">Co-Chair</option>
                                    <option value="Cognate">Cognate</option>
                                    <option value="Major">Major</option>
                                    <option value="Minor">Minor</option>
                                    <option value="Member">Member</option>
                                </select>
                                
                                <div class="pt-1">
                                    <div class="text-[9px] text-gray-400 dark:text-gray-500 font-medium mb-0.5">Appointed Date</div>
                                    <input type="date" wire:model="editingAppointedDate" class="text-xs rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-850 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 w-full py-0.5 px-1.5 shadow-sm" />
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <div class="space-y-1.5 min-w-[140px]">
                                <div>
                                    <div class="text-[9px] text-gray-400 dark:text-gray-500 font-medium mb-0.5">Term Start</div>
                                    <select wire:model="editingTermStartId" class="text-xs rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-850 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 w-full py-0.5 px-1.5 shadow-sm">
                                        <option value="">None</option>
                                        @foreach(\App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')->get() as $sem)
                                            <option value="{{ $sem->id }}">{{ $sem->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <div class="text-[9px] text-gray-400 dark:text-gray-500 font-medium mb-0.5">Term End</div>
                                    <select wire:model="editingTermEndId" class="text-xs rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-850 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 w-full py-0.5 px-1.5 shadow-sm">
                                        <option value="">None</option>
                                        @foreach(\App\Models\Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')->get() as $sem)
                                            <option value="{{ $sem->id }}">{{ $sem->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-1 pt-1">
                                <button
                                    type="button"
                                    wire:click.prevent.stop="saveAssignment"
                                    class="inline-flex items-center justify-center gap-0.5 px-2 py-1 rounded text-xs font-semibold bg-success-50 text-success-700 hover:bg-success-100 dark:bg-success-900/20 dark:text-success-400 transition-colors shadow-sm border border-success-200/50 dark:border-success-800/30"
                                >
                                    <x-heroicon-m-check class="w-3.5 h-3.5" />
                                    Save
                                </button>
                                <button
                                    type="button"
                                    wire:click.prevent.stop="cancelEditingAssignment"
                                    class="inline-flex items-center justify-center gap-0.5 px-2 py-1 rounded text-xs font-semibold bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-white/5 dark:text-gray-400 transition-colors border border-gray-200/30 dark:border-white/10"
                                >
                                    <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                    Cancel
                                </button>
                            </div>
                        </td>
                    @else
                        {{-- Static View --}}
                        <td class="px-3 py-2 align-top">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if($cm->role === 'Adviser') bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300
                                @elseif($cm->role === 'Co-Adviser') bg-success-50 text-success-700 dark:bg-success-900/20 dark:text-success-400
                                @elseif(in_array($cm->role, ['Chair', 'Co-Chair'])) bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300
                                @else bg-gray-100 text-gray-800 dark:bg-white/10 dark:text-gray-300
                                @endif">
                                {{ $cm->role }}
                            </span>
                            @if($cm->appointed_date)
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                                    Appointed: {{ \Carbon\Carbon::parse($cm->appointed_date)->format('M j, Y') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top text-xs text-gray-500 dark:text-gray-400">
                            @if($cm->termStart || $cm->termEnd)
                                {{ $cm->termStart?->label ?? '—' }}
                                @if($cm->termEnd)
                                    → {{ $cm->termEnd->label }}
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-600 italic">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex items-center gap-1">
                                <button
                                    type="button"
                                    wire:click.prevent.stop="startEditingAssignment({{ $cm->id }})"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20 transition-colors"
                                    title="Edit role & terms"
                                >
                                    <x-heroicon-m-pencil-square class="w-3.5 h-3.5" />
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    wire:click.prevent.stop="removeAssignment({{ $cm->id }})"
                                    wire:confirm="Remove {{ $cm->student->full_name }} as {{ $cm->role }}?"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs text-danger-600 hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-900/20 transition-colors"
                                >
                                    <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                    Remove
                                </button>
                            </div>
                        </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="mb-4 text-sm text-gray-500 dark:text-gray-400 italic">
    No students currently assigned to this faculty member.
</div>
@endif
