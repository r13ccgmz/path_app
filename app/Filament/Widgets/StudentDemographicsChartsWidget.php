<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Enrollee;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Widget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Livewire\Attributes\On;

class StudentDemographicsChartsWidget extends Widget implements HasForms
{
    use InteractsWithForms;
    protected static ?int $sort = 3;
    protected string $view = 'filament.widgets.student-demographics-charts-widget';

    protected int|string|array $columnSpan = 'full';

    public ?array $termFilter = [];
    public array $chartData = [];

    public function mount(): void
    {
        $this->chartData = $this->getDemographicsData();
    }

    public function rendering(): void
    {
        $this->chartData = $this->getDemographicsData();
    }

    public function updatedTermFilter(): void
    {
        $this->dispatch('studentTermFilterUpdated', termFilter: $this->termFilter);
    }

    #[On('studentTermFilterUpdated')]
    public function handleTermFilterUpdated(array $termFilter): void
    {
        if ($this->termFilter !== $termFilter) {
            $this->termFilter = $termFilter;
            $this->form->fill(['termFilter' => $termFilter]);
            $this->chartData = $this->getDemographicsData();
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('termFilter')
                    ->multiple()
                    ->options($this->terms)
                    ->label('')
                    ->placeholder('Filter by Terms (All)')
                    ->live()
                    ->extraAttributes(['class' => 'min-w-[200px]'])
            ]);
    }

    public function getTermsProperty(): array
    {
        return \App\Models\Semester::with('academicYear')
            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
            ->get()
            ->mapWithKeys(fn($s) => [$s->term_code => "[{$s->term_code}] {$s->label}"])
            ->toArray();
    }

    public function getDemographicsData(): array
    {
        // 1. Status Chart (Removed due to redundancy with Overview Widget)

        // 2. Gender Distribution (from Enrollee)
        $genderQuery = DB::table('enrollees')
            ->select('sex', DB::raw('COUNT(DISTINCT student_number) as count'))
            ->whereNotNull('sex')
            ->where('sex', '!=', '');

        if (!empty($this->termFilter)) {
            $genderQuery->whereIn('term_id', $this->termFilter);
        }

        $genderCounts = $genderQuery->groupBy('sex')->get();

        $genderMap = [
            'F' => 'Female',
            'M' => 'Male',
            'female' => 'Female',
            'male' => 'Male',
        ];

        $genders = ['Female' => 0, 'Male' => 0, 'Other' => 0];
        foreach ($genderCounts as $g) {
            $label = $genderMap[$g->sex] ?? 'Other';
            $genders[$label] += $g->count;
        }

        // Clean up zeros
        $genders = array_filter($genders, fn($val) => $val > 0);

        // 3. Full-time vs Part-time
        $threshold = (int) \App\Models\SystemSetting::get('full_time_units_threshold', 9);

        // Get the latest enrollee record ID for each student in the selected terms
        $latestEnrolleeIdsQuery = DB::table('enrollees')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('student_number');

        if (!empty($this->termFilter)) {
            $latestEnrolleeIdsQuery->whereIn('term_id', $this->termFilter);
        }

        $ftPtCounts = DB::table('enrollees')
            ->whereIn('id', $latestEnrolleeIdsQuery)
            ->select(
                DB::raw('CASE WHEN total_units >= ' . $threshold . ' THEN "Full-Time" ELSE "Part-Time" END as status'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('status')
            ->get();

        $ftPtLabels = [];
        $ftPtData = [];
        foreach ($ftPtCounts as $ftpt) {
            $ftPtLabels[] = $ftpt->status;
            $ftPtData[] = $ftpt->count;
        }

        // 4. Nationality Profile (from Enrollee)
        $natQuery = DB::table('enrollees')
            ->select('nationality', DB::raw('COUNT(DISTINCT student_number) as count'))
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '');

        if (!empty($this->termFilter)) {
            $natQuery->whereIn('term_id', $this->termFilter);
        }

        $natCounts = $natQuery->groupBy('nationality')->get();

        $nationalities = ['Filipino' => 0, 'International' => 0];
        $internationalBreakdown = [];

        foreach ($natCounts as $n) {
            // Parse nationality JSON array if needed
            $nats = json_decode($n->nationality, true);
            if (!$nats || !is_array($nats)) {
                $nats = [$n->nationality];
            }

            foreach ($nats as $nat) {
                if (strtolower($nat) === 'filipino') {
                    $nationalities['Filipino'] += $n->count;
                } else {
                    $nationalities['International'] += $n->count;
                    $internationalBreakdown[$nat] = ($internationalBreakdown[$nat] ?? 0) + $n->count;
                }
            }
        }

        return [
            'gender' => [
                'labels' => array_keys($genders),
                'data' => array_values($genders),
                'colors' => ['#ec4899', '#1A2B6B', '#9ca3af'] // Pink, CPAf Navy, Gray
            ],
            'enrollment' => [
                'threshold' => $threshold,
                'labels' => $ftPtLabels,
                'data' => $ftPtData,
                'colors' => ['#1A5C38', '#f59e0b'] // CPAf Green, Amber
            ],
            'nationality' => [
                'summary' => [
                    'labels' => array_keys($nationalities),
                    'data' => array_values($nationalities),
                    'colors' => ['#1A2B6B', '#ef4444'] // CPAf Navy, Red
                ],
                'breakdown' => $internationalBreakdown
            ]
        ];
    }
}
