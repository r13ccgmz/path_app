<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Enrollee;
use App\Models\Semester;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait HasTermRangeFilter
{
    use InteractsWithPageFilters;

    /** Descriptions for tooltip display: term_code => "1st Semester — AY 2021-2022" */
    public array $termDescriptions = [];

    /**
     * Get all distinct term codes as select options: code => "[code] Label"
     */
    public function getTermOptions(): array
    {
        $termCodes = Enrollee::select('term_id')
            ->distinct()
            ->orderBy('term_id')
            ->pluck('term_id')
            ->toArray();

        $semesters = Semester::whereIn('term_code', $termCodes)
            ->with('academicYear')
            ->get()
            ->keyBy('term_code');

        $options = [];
        foreach ($termCodes as $code) {
            $semester = $semesters->get($code);
            $label = $semester ? "[{$code}] {$semester->label}" : "[{$code}]";
            $options[$code] = $label;
        }

        return $options;
    }

    /**
     * Build term descriptions map for tooltips.
     */
    protected function buildTermDescriptions(array $termCodes): void
    {
        $semesters = Semester::whereIn('term_code', $termCodes)
            ->with('academicYear')
            ->get()
            ->keyBy('term_code');

        $this->termDescriptions = [];
        foreach ($termCodes as $code) {
            $semester = $semesters->get($code);
            $this->termDescriptions[(string) $code] = $semester ? $semester->label : '';
        }
    }

    /**
     * Apply from/to filter to a query or array of term codes.
     */
    protected function filterTermRange(array $termCodes): array
    {
        $filterFrom = $this->filters['filterFrom'] ?? null;
        $filterTo = $this->filters['filterTo'] ?? null;

        if (!empty($filterFrom)) {
            $termCodes = array_filter($termCodes, fn ($code) => $code >= $filterFrom);
        }
        if (!empty($filterTo)) {
            $termCodes = array_filter($termCodes, fn ($code) => $code <= $filterTo);
        }
        return array_values($termCodes);
    }
}
