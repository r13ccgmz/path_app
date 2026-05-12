{{-- Student List Guide + All Students Table (default view) --}}
<div>
    {{-- Helper Guide --}}
    <div class="rounded-xl border border-primary-200 dark:border-primary-800/50 bg-primary-50/50 dark:bg-primary-900/10 px-5 py-4 mb-6">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" />
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="font-semibold text-gray-800 dark:text-gray-200 mb-1">List of Students</p>
                <p>Browse and manage all student records. Click on a student row to view their full enrollment history, academic progress, milestones, and graduation information. Use the filters to narrow results by status, program, adviser, or admission semester.</p>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
            <x-heroicon-o-user-group class="w-6 h-6 text-gray-400" />
            All Students
        </h3>
        <div>
            {{ $this->getAction('addStudent') }}
        </div>
    </div>
    {{ $this->table }}
</div>
