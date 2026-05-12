<div class="space-y-6">
    <!-- Advisees Section -->
    <div class="space-y-4">
        <h3 class="text-lg font-medium tracking-tight text-gray-950 dark:text-white">Active Advisees</h3>
        
        @php
            $advisees = $faculty->advisees()->with('program')->where('student_status', 'active')->get();
        @endphp

        @if($advisees->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No active advisees.</p>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Student Name</th>
                            <th class="px-4 py-3 font-medium">Student No.</th>
                            <th class="px-4 py-3 font-medium">Program</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($advisees as $student)
                            <tr>
                                <td class="px-4 py-3 text-gray-950 dark:text-white">{{ $student->full_name }}</td>
                                <td class="px-4 py-3">{{ \App\Models\Enrollee::formatStudentNumber($student->student_number) }}</td>
                                <td class="px-4 py-3">{{ $student->program?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300">
                                        {{ ucfirst($student->student_status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Committee Memberships Section -->
    <div class="space-y-4">
        <h3 class="text-lg font-medium tracking-tight text-gray-950 dark:text-white">Committee Assignments</h3>
        
        @php
            $memberships = $faculty->committeeMemberships()
                ->with(['student.program'])
                ->whereHas('student', function ($query) {
                    $query->where('student_status', 'active');
                })
                ->orderBy('role')
                ->get();
        @endphp

        @if($memberships->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No active committee assignments.</p>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Student Name</th>
                            <th class="px-4 py-3 font-medium">Program</th>
                            <th class="px-4 py-3 font-medium">Role</th>
                            <th class="px-4 py-3 font-medium">Appointed Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($memberships as $membership)
                            <tr>
                                <td class="px-4 py-3 text-gray-950 dark:text-white">{{ $membership->student->full_name }}</td>
                                <td class="px-4 py-3">{{ $membership->student->program?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded text-xs font-medium border
                                        @if(in_array($membership->role, ['chair', 'co-chair'])) bg-warning-100 text-warning-800 border-warning-200 dark:bg-warning-900/30 dark:text-warning-300 dark:border-warning-700
                                        @else bg-gray-100 text-gray-800 border-gray-200 dark:bg-white/10 dark:text-gray-300 dark:border-white/10 @endif">
                                        {{ ucwords(str_replace('-', ' ', $membership->role)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $membership->appointed_date?->format('M j, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
