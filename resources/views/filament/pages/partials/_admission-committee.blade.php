{{-- Admission, Advisers & Advisory Committee Section --}}
<x-filament::section icon="heroicon-o-academic-cap" icon-color="primary" collapsible collapsed>
    <x-slot name="heading">Admission, Advisers & Committee</x-slot>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-12 gap-y-6">
        <div>
            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Applicant Status</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ empty($studentInfo['applicant_status']) ? '-' : ucwords(str_replace('-', ' ', $studentInfo['applicant_status'])) }}</span>
        </div>
        <div>
            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Admission Semester</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $studentInfo['admission_semester'] ?? '-' }}</span>
        </div>
        <div>
            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Admission Date</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $studentInfo['admission_date'] ?? '-' }}</span>
        </div>
        <div>
            <span class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Registration Adviser</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $studentInfo['registration_adviser_name'] ?? '-' }}{{ !empty($studentInfo['registration_adviser_designation']) ? ' (' . $studentInfo['registration_adviser_designation'] . ')' : '' }}</span>
            @if(!empty($studentInfo['registration_adviser_appointed_date']))
                <span class="block text-xs text-gray-400 dark:text-gray-500">Appointed: {{ $studentInfo['registration_adviser_appointed_date'] }}</span>
            @endif
        </div>
    </div>

    @if (!empty($studentInfo['committee_members']))
    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Advisory Committee</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            @php
                $advisers = collect($studentInfo['committee_members'])->filter(fn($cm) => in_array($cm['role'] ?? '', ['Adviser', 'Former Adviser']))->all();
                $others = collect($studentInfo['committee_members'])->filter(fn($cm) => !in_array($cm['role'] ?? '', ['Adviser', 'Former Adviser']))->all();
            @endphp
            
            @foreach ($advisers as $adv)
                <div class="flex items-baseline md:col-span-2 pb-3 mb-1 border-b border-gray-100 dark:border-gray-700/50">
                    <span class="w-28 text-sm font-semibold text-primary-600 dark:text-primary-400">
                        @if(($adv['role'] ?? '') === 'Adviser')
                            <x-heroicon-o-star class="w-3.5 h-3.5 inline -mt-0.5 mr-0.5" />Primary Adviser:
                        @else
                            Former Adviser:
                        @endif
                    </span>
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $adv['name'] }}{{ !empty($adv['designation']) ? ' (' . $adv['designation'] . ')' : '' }}</span>
                        @if (!empty($adv['appointed_date']))
                            <span class="text-xs text-gray-400 dark:text-gray-500">Appointed: {{ $adv['appointed_date'] }}</span>
                        @endif
                        @if(!empty($adv['term_start']) || !empty($adv['term_end']))
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                Term: {{ $adv['term_start'] ?? '-' }} to {{ $adv['term_end'] ?? 'Present' }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach

            @foreach ($others as $cm)
                <div class="flex items-baseline">
                    <span class="w-28 text-sm text-gray-500 dark:text-gray-400">{{ $cm['role'] }}:</span>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $cm['name'] }}{{ !empty($cm['designation']) ? ' (' . $cm['designation'] . ')' : '' }}</span>
                        @if(!empty($cm['appointed_date']))
                            <span class="text-xs text-gray-400 dark:text-gray-500">Appointed: {{ $cm['appointed_date'] }}</span>
                        @endif
                        @if(!empty($cm['term_start']) || !empty($cm['term_end']))
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                Term: {{ $cm['term_start'] ?? '-' }} to {{ $cm['term_end'] ?? 'Present' }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</x-filament::section>
