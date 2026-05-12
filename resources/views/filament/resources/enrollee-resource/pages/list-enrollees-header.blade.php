<x-filament-panels::header
    :actions="$this->getCachedHeaderActions()"
    :breadcrumbs="filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : []"
    :heading="$this->getHeading()"
    :subheading="$this->getSubheading()"
>
</x-filament-panels::header>

{{-- Helper Guide --}}
<div class="rounded-xl border border-primary-200 dark:border-primary-800/50 bg-primary-50/50 dark:bg-primary-900/10 px-5 py-4 mb-2">
    <div class="flex items-start gap-3">
        <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" />
        <div class="text-sm text-gray-600 dark:text-gray-400">
            <p class="font-semibold text-gray-800 dark:text-gray-200 mb-1">List of Enrollees</p>
            <p>This table shows <strong>raw enrollment data</strong> imported from the Graduate School. Each row is one enrollment record per student per term. Use <strong>Upload Excel</strong> to import new data. To edit student details (adviser, status, etc.), go to <a href="/admin/list-of-students" class="text-primary-600 dark:text-primary-400 underline font-medium">List of Students</a>.</p>
            <p class="mt-1 text-xs">Filter by <strong>Term</strong> to view a specific semester's enrollment, or by <strong>Degree/Program</strong> to narrow down to a specific program.</p>
        </div>
    </div>
</div>
