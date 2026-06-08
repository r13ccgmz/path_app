<x-filament-panels::page>
    <div wire:key="academic-years-page-container">
        <div class="flex justify-center mb-6" wire:key="academic-years-tabs-container">
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="$activeTab === 'academic_years'"
                    wire:click="$set('activeTab', 'academic_years')"
                    icon="heroicon-o-calendar"
                    :badge="$this->getAcademicYearsCount()"
                    wire:key="tab-item-academic-years"
                >
                    Academic Years
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$activeTab === 'term_codes'"
                    wire:click="$set('activeTab', 'term_codes')"
                    icon="heroicon-o-hashtag"
                    :badge="$this->getTermCodesCount()"
                    wire:key="tab-item-term-codes"
                >
                    Term Codes
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>

        <div class="mt-6" wire:key="academic-years-content-container">
            @if ($activeTab === 'academic_years')
                <div wire:key="academic-years-table-wrapper">
                    {{ $this->table }}
                </div>
            @else
                <div wire:key="term-codes-table-wrapper">
                    @livewire(\App\Filament\Widgets\TermCodesTableWidget::class, [], key('term-codes-table-widget'))
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
