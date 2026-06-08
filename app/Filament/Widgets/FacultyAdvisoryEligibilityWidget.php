<?php

namespace App\Filament\Widgets;

use App\Models\Faculty;
use App\Models\StudentCommitteeMember;
use App\Models\Semester;
use Filament\Widgets\Widget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class FacultyAdvisoryEligibilityWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    // Render manually on the Mentorship Monitoring page
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.faculty-advisory-eligibility-widget';

    protected int | string | array $columnSpan = 'full';

    // Reactive property passed from page-level filters
    public ?array $semesterIds = null;

    // Toggle to only show the advising load chart and hide metrics/compliance audits
    public bool $onlyChart = false;

    // Local form filter properties
    public ?array $termFilter = [];
    public ?string $sortBy = 'name_asc';
    public array $chartData = [];
    public array $metrics = [];
    public array $violations = [];

    protected bool $isInitialized = false;

    public function mount(): void
    {
        $this->termFilter = $this->semesterIds ?? [];
        $this->recomputeData();
    }

    public function rendering(): void
    {
        $this->recomputeData();
    }

    public function updatedSemesterIds(): void
    {
        if (!$this->isInitialized && !empty($this->semesterIds)) {
            $this->termFilter = $this->semesterIds;
            $this->isInitialized = true;
        }
        $this->recomputeData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-eligibility-chart"))');
    }

    public function updatedTermFilter(): void
    {
        $this->recomputeData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-eligibility-chart"))');
    }

    public function updatedSortBy(): void
    {
        $this->recomputeData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-eligibility-chart"))');
    }

    #[On('assignment-changed')]
    public function refreshData(): void
    {
        $this->recomputeData();
        $this->js('window.dispatchEvent(new CustomEvent("refresh-eligibility-chart"))');
    }

    protected function recomputeData(): void
    {
        $data = $this->getTrackingAndEligibilityData();
        $this->chartData = $data['chart'] ?? ['labels' => [], 'phd' => [], 'masters' => []];
        $this->metrics = $data['metrics'] ?? [];
        $this->violations = $data['violations'] ?? [];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('termFilter')
                    ->multiple()
                    ->options($this->terms)
                    ->label('Term filter')
                    ->placeholder('Filter by Terms (All)')
                    ->live()
                    ->extraAttributes(['class' => 'min-w-[220px]']),

                Select::make('sortBy')
                    ->options([
                        'name_asc' => '🔤 Sort by Name (A → Z)',
                        'name_desc' => '🔤 Sort by Name (Z → A)',
                        'total' => '📊 Sort by Total Advisees',
                        'phd' => '📊 Sort by PhD Advisees',
                        'masters' => '📊 Sort by Master\'s/MS Advisees',
                    ])
                    ->label('Sort by')
                    ->selectablePlaceholder(false)
                    ->live()
                    ->extraAttributes(['class' => 'w-[220px]'])
            ])
            ->columns(2);
    }

    public function getTermsProperty(): array
    {
        return Semester::with('academicYear')
            ->orderByRaw('CAST(term_code AS UNSIGNED) DESC')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => "[{$s->term_code}] {$s->label}"])
            ->toArray();
    }

    public function getTrackingAndEligibilityData(): array
    {
        $scmTermCond = '';

        // Use widget's own term filter first, fallback to parent page semester filters
        $activeTerms = !empty($this->termFilter) ? $this->termFilter : ($this->semesterIds ?? []);

        // Handle reactive semester filters
        if (!empty($activeTerms)) {
            $ids = array_map('intval', $activeTerms);
            $scmTermCond = ' AND (scm.term_start_id IN (' . implode(',', $ids) . ') OR scm.term_end_id IN (' . implode(',', $ids) . '))';
        } else {
            // No filter selected: show ALL assignments regardless of term
            $scmTermCond = '';
        }

        // 1. Query all active advising assignments
        // Active advising means roles 'Adviser' or 'Co-Adviser'
        $assignments = DB::select("
            SELECT 
                scm.id as assignment_id,
                scm.role as assignment_role,
                scm.faculty_id,
                f.first_name as faculty_first_name,
                f.last_name as faculty_last_name,
                f.middle_name as faculty_middle_name,
                f.suffix as faculty_suffix,
                f.highest_degree as faculty_highest_degree,
                scm.student_id,
                s.surname as student_surname,
                s.given_name as student_given_name,
                s.student_number as student_number,
                p.code as program_code,
                p.degree_level as program_degree_level
            FROM student_committee_members scm
            INNER JOIN faculty f ON f.id = scm.faculty_id
            INNER JOIN students s ON s.id = scm.student_id
            INNER JOIN programs p ON p.id = s.program_id
            WHERE scm.role IN ('Adviser', 'Co-Adviser')
              AND s.deleted_at IS NULL
              AND f.deleted_at IS NULL
              {$scmTermCond}
        ");

        // 2. Compute Dashboard Metrics from active assignments
        $uniqueStudentIds = [];
        $uniqueFacultyIds = [];
        $phdStudentIds = [];
        $mastersStudentIds = [];
        $totalAssignments = count($assignments);
        $phdAssignmentsCount = 0;
        $mastersAssignmentsCount = 0;

        foreach ($assignments as $a) {
            $uniqueStudentIds[$a->student_id] = true;
            $uniqueFacultyIds[$a->faculty_id] = true;

            if ($a->program_degree_level === 'doctorate') {
                $phdStudentIds[$a->student_id] = true;
                $phdAssignmentsCount++;
            } elseif (in_array($a->program_degree_level, ['master', 'master_of_science'])) {
                $mastersStudentIds[$a->student_id] = true;
                $mastersAssignmentsCount++;
            }
        }

        $totalActiveAdvisees = count($uniqueStudentIds);
        $totalPhDAdvisees = count($phdStudentIds);
        $totalMastersAdvisees = count($mastersStudentIds);
        $activeFacultyCount = count($uniqueFacultyIds);

        // 3. Audit for Eligibility Violations
        $violations = [];
        foreach ($assignments as $a) {
            $facDegree = $a->faculty_highest_degree;
            $progLevel = $a->program_degree_level;
            $isViolation = false;
            $reason = '';

            // Rule 1: PhD students (doctorate) require doctorate faculty
            if ($progLevel === 'doctorate') {
                if ($facDegree !== 'doctorate') {
                    $isViolation = true;
                    $reason = "PhD candidate requires Doctorate-holding adviser/co-adviser. (Highest degree: " . $this->getHighestDegreeLabel($facDegree) . ")";
                }
            } 
            // Rule 2: Master's/MS students require at least master's or doctorate faculty
            elseif (in_array($progLevel, ['master', 'master_of_science'])) {
                if (!in_array($facDegree, ['doctorate', 'masters'])) {
                    $isViolation = true;
                    $reason = "Master's/MS student requires Doctorate or Master's-holding adviser/co-adviser. (Highest degree: " . $this->getHighestDegreeLabel($facDegree) . ")";
                }
            }

            if ($isViolation) {
                $facultyName = implode(', ', array_filter([$a->faculty_last_name, $a->faculty_first_name]));
                if ($a->faculty_middle_name) {
                    $facultyName .= ' ' . substr($a->faculty_middle_name, 0, 1) . '.';
                }
                if ($a->faculty_suffix) {
                    $facultyName .= ' ' . $a->faculty_suffix;
                }

                $studentName = implode(', ', array_filter([$a->student_surname, $a->student_given_name]));
                $studentNum = $a->student_number;
                $formattedNum = substr($studentNum, 0, 4) . '-' . substr($studentNum, 4);

                $violations[$a->faculty_id]['faculty_name'] = $facultyName;
                $violations[$a->faculty_id]['highest_degree'] = $this->getHighestDegreeLabel($facDegree);
                $violations[$a->faculty_id]['assignments'][] = [
                    'student_name' => $studentName,
                    'student_number' => $formattedNum,
                    'program_code' => $a->program_code,
                    'program_level' => $progLevel === 'doctorate' ? 'Doctorate' : 'Master\'s',
                    'role' => $a->assignment_role,
                    'reason' => $reason
                ];
            }
        }

        // Sort violations by faculty name alphabetically
        uasort($violations, fn($x, $y) => strcmp($x['faculty_name'], $y['faculty_name']));

        // 4. Compute Faculty Graduate Advising Load Chart Data
        $phdCond = "SELECT COUNT(*) FROM student_committee_members scm 
                    INNER JOIN students s ON s.id = scm.student_id 
                    INNER JOIN programs p ON p.id = s.program_id 
                    WHERE scm.faculty_id = faculty.id 
                      AND scm.role IN ('Adviser', 'Co-Adviser') 
                      AND s.deleted_at IS NULL 
                      AND p.degree_level = 'doctorate' 
                      {$scmTermCond}";

        $mastersCond = "SELECT COUNT(*) FROM student_committee_members scm 
                        INNER JOIN students s ON s.id = scm.student_id 
                        INNER JOIN programs p ON p.id = s.program_id 
                        WHERE scm.faculty_id = faculty.id 
                          AND scm.role IN ('Adviser', 'Co-Adviser') 
                          AND s.deleted_at IS NULL 
                          AND p.degree_level IN ('master', 'master_of_science') 
                          {$scmTermCond}";

        $faculties = Faculty::where('is_external', false)
            ->whereNull('deleted_at')
            ->addSelect([
                'faculty.*',
                DB::raw("({$phdCond}) as phd_count"),
                DB::raw("({$mastersCond}) as masters_count"),
            ])
            ->get()
            ->filter(function ($f) {
                return in_array($f->highest_degree, ['doctorate', 'masters']) || $f->phd_count > 0 || $f->masters_count > 0;
            });

        // Apply sorting based on $this->sortBy selection
        $sortBy = $this->sortBy ?? 'name_asc';

        if ($sortBy === 'name_asc') {
            $faculties = $faculties->sortBy([
                ['last_name', 'asc'],
                ['first_name', 'asc']
            ]);
        } elseif ($sortBy === 'name_desc') {
            $faculties = $faculties->sortBy([
                ['last_name', 'desc'],
                ['first_name', 'desc']
            ]);
        } elseif ($sortBy === 'total') {
            $faculties = $faculties->sortByDesc(fn ($f) => $f->phd_count + $f->masters_count);
        } elseif ($sortBy === 'phd') {
            $faculties = $faculties->sortByDesc('phd_count');
        } elseif ($sortBy === 'masters') {
            $faculties = $faculties->sortByDesc('masters_count');
        }

        $faculties = $faculties->values();

        $chartLabels = [];
        $chartPhd = [];
        $chartMasters = [];

        foreach ($faculties as $f) {
            $formattedName = $f->last_name . ', ' . substr($f->first_name, 0, 1) . '.';
            $chartLabels[] = $formattedName;
            $chartPhd[] = (int) $f->phd_count;
            $chartMasters[] = (int) $f->masters_count;
        }

        return [
            'metrics' => [
                'total_advisees' => $totalActiveAdvisees,
                'phd_advisees' => $totalPhDAdvisees,
                'masters_advisees' => $totalMastersAdvisees,
                'active_faculty' => $activeFacultyCount,
                'total_assignments' => $totalAssignments,
                'phd_assignments' => $phdAssignmentsCount,
                'masters_assignments' => $mastersAssignmentsCount,
            ],
            'violations' => $violations,
            'chart' => [
                'labels' => $chartLabels,
                'phd' => $chartPhd,
                'masters' => $chartMasters,
            ]
        ];
    }

    private function getHighestDegreeLabel(?string $degree): string
    {
        return match ($degree) {
            'high-school' => 'High School',
            'vocational' => 'Vocational',
            'bachelors' => "Bachelor's",
            'masters' => "Master's",
            'doctorate' => 'Doctorate',
            'n/a' => 'N/A',
            null, '' => 'None Specified',
            default => ucfirst($degree),
        };
    }
}
