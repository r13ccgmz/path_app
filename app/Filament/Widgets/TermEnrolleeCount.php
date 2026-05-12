<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Enrollee;
use App\Models\Semester;
use App\Exports\EnrollmentProgramExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TermEnrolleeCount extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.term-enrollee-count';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 3;

    public function getTermOptions(): array
    {
        $termCodes = Enrollee::distinct()
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
            $label = $semester ? $semester->label : 'Undefined Term';
            $options[$code] = "[{$code}] {$label}";
        }
        return $options;
    }

    protected function getFilteredTermIds(): ?array
    {
        $filterFrom = $this->filters['filterFrom'] ?? null;
        $filterTo = $this->filters['filterTo'] ?? null;

        if (empty($filterFrom) && empty($filterTo)) {
            return null; // null = all terms
        }

        $query = Enrollee::select('term_id')->distinct();
        if (!empty($filterFrom)) {
            $query->where('term_id', '>=', $filterFrom);
        }
        if (!empty($filterTo)) {
            $query->where('term_id', '<=', $filterTo);
        }
        return $query->pluck('term_id')->toArray();
    }

    public function getEnrolleeCount(): int
    {
        $terms = $this->getFilteredTermIds();
        if ($terms === null) {
            return Enrollee::distinct('student_number')->count('student_number');
        }
        return Enrollee::whereIn('term_id', $terms)
            ->distinct('student_number')
            ->count('student_number');
    }

    public function getCourseRegistrationsCount(): int
    {
        $terms = $this->getFilteredTermIds();
        if ($terms === null) {
            return DB::table('enrollment_course_enrollee')->count();
        }
        $enrolleeIds = Enrollee::whereIn('term_id', $terms)->pluck('id');
        return DB::table('enrollment_course_enrollee')
            ->whereIn('enrollee_id', $enrolleeIds)
            ->count();
    }

    public function getProgramBreakdown(): array
    {
        $query = Enrollee::select('degree_program', DB::raw('COUNT(DISTINCT student_number) as count'));

        $terms = $this->getFilteredTermIds();
        if ($terms !== null) {
            $query->whereIn('term_id', $terms);
        }

        return $query->groupBy('degree_program')
            ->orderByDesc('count')
            ->get()
            ->map(fn($row) => [
                'program' => $row->degree_program ?: '(No Program)',
                'count' => $row->count,
            ])
            ->toArray();
    }

    public function exportProgram(string $program)
    {
        $actualProgram = $program === '(No Program)' ? '' : $program;
        $filename = 'enrollees_' . ($actualProgram ? str_replace(' ', '_', $actualProgram) : 'unassigned') . '.xlsx';

        $terms = $this->getFilteredTermIds();
        return Excel::download(
            new EnrollmentProgramExport([$actualProgram], $terms ?? []),
            $filename
        );
    }
}
