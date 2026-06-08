<?php

namespace App\Filament\Widgets;

use App\Models\Faculty;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class MentorshipStatsWidget extends BaseWidget
{
    // This widget is NOT auto-discovered on the main dashboard.
    // It is only rendered manually in the mentorship-monitoring page view.
    protected static bool $isDiscovered = false;

    // Reactive property passed from page-level filters
    public ?array $semesterIds = null;

    #[On('assignment-changed')]
    public function refreshData(): void
    {
        // Livewire automatically re-renders the component when this listener is triggered.
    }

    protected function getStats(): array
    {
        $activeFaculty = Faculty::where('is_external', false)
            ->where('faculty_status', 'active')
            ->count();

        $activeTerms = $this->semesterIds;

        $studentQuery = DB::table('student_committee_members')
            ->whereNotNull('faculty_id');

        $graduateQuery = DB::table('graduate_committee_members')
            ->whereNotNull('faculty_id');

        $aoQuery = DB::table('academic_output_committee')
            ->whereNotNull('faculty_id');

        if (!empty($activeTerms)) {
            $studentQuery->where(function ($query) use ($activeTerms) {
                $query->whereIn('term_start_id', $activeTerms)
                    ->orWhereIn('term_end_id', $activeTerms);
            });
            $graduateQuery->where(function ($query) use ($activeTerms) {
                $query->whereIn('term_start_id', $activeTerms)
                    ->orWhereIn('term_end_id', $activeTerms);
            });
            $aoQuery->where(function ($query) use ($activeTerms) {
                $query->whereIn('term_start_id', $activeTerms)
                    ->orWhereIn('term_end_id', $activeTerms);
            });
        }

        $studentCommitteeCount = $studentQuery->count();
        $graduateCommitteeCount = $graduateQuery->count();
        $aoCommitteeCount = $aoQuery->count();

        $totalAssignments = $studentCommitteeCount + $graduateCommitteeCount + $aoCommitteeCount;

        // Faculty with any assignment across all 3 tables
        $facultyWithAssignmentsQuery = "
            SELECT COUNT(DISTINCT f.id) as cnt
            FROM faculty f
            WHERE f.is_external = 0
              AND f.faculty_status = 'active'
              AND f.deleted_at IS NULL
        ";
        
        if (!empty($activeTerms)) {
            $ids = implode(',', array_map('intval', $activeTerms));
            $facultyWithAssignmentsQuery .= "
              AND (
                EXISTS (SELECT 1 FROM student_committee_members scm WHERE scm.faculty_id = f.id AND (scm.term_start_id IN ({$ids}) OR scm.term_end_id IN ({$ids})))
                OR EXISTS (SELECT 1 FROM graduate_committee_members gcm WHERE gcm.faculty_id = f.id AND (gcm.term_start_id IN ({$ids}) OR gcm.term_end_id IN ({$ids})))
                OR EXISTS (SELECT 1 FROM academic_output_committee aoc WHERE aoc.faculty_id = f.id AND (aoc.term_start_id IN ({$ids}) OR aoc.term_end_id IN ({$ids})))
              )
            ";
        } else {
            $facultyWithAssignmentsQuery .= "
              AND (
                EXISTS (SELECT 1 FROM student_committee_members scm WHERE scm.faculty_id = f.id)
                OR EXISTS (SELECT 1 FROM graduate_committee_members gcm WHERE gcm.faculty_id = f.id)
                OR EXISTS (SELECT 1 FROM academic_output_committee aoc WHERE aoc.faculty_id = f.id)
              )
            ";
        }
        $facultyWithAssignments = DB::selectOne($facultyWithAssignmentsQuery)->cnt;

        $avgLoad = $activeFaculty > 0 ? round($totalAssignments / $activeFaculty, 1) : 0;

        return [
            Stat::make('Active Faculty', $activeFaculty)
                ->description("{$facultyWithAssignments} with assignments")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Student Committee', number_format($studentCommitteeCount))
                ->description('Advisory assignments')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
            Stat::make('Graduate Committee', number_format($graduateCommitteeCount))
                ->description('From graduate imports')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('warning'),
            Stat::make('Academic Output', number_format($aoCommitteeCount))
                ->description('Thesis/dissertation panels')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info'),
        ];
    }
}
