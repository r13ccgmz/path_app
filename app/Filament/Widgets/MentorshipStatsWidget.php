<?php

namespace App\Filament\Widgets;

use App\Models\Faculty;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MentorshipStatsWidget extends BaseWidget
{
    // This widget is NOT auto-discovered on the main dashboard.
    // It is only rendered manually in the mentorship-monitoring page view.
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $activeFaculty = Faculty::where('is_external', false)
            ->where('faculty_status', 'active')
            ->count();

        $mastersAdvisees = Student::whereHas('committeeMembers', fn ($q) => $q->where('role', 'Adviser'))
            ->whereHas('program', fn ($q) => $q->whereIn('degree_level', ['master', 'master_of_science']))
            ->count();

        $phdAdvisees = Student::whereHas('committeeMembers', fn ($q) => $q->where('role', 'Adviser'))
            ->whereHas('program', fn ($q) => $q->where('degree_level', 'doctorate'))
            ->count();

        $totalAdvisees = $mastersAdvisees + $phdAdvisees;
        $avgAdvisees = $activeFaculty > 0 ? round($totalAdvisees / $activeFaculty, 1) : 0;

        // Overloaded faculty (5+ advisees as default threshold)
        $overloaded = Faculty::where('is_external', false)
            ->where('faculty_status', 'active')
            ->withCount('adviseStudents')
            ->get()
            ->filter(fn ($f) => $f->advise_students_count >= 5)
            ->count();

        return [
            Stat::make('Active Faculty', $activeFaculty)
                ->description('Non-external, active accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make("Master's Advisees", $mastersAdvisees)
                ->description('Currently assigned')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),
            Stat::make('PhD Advisees', $phdAdvisees)
                ->description('Currently assigned')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
            Stat::make('Avg Advisees / Faculty', $avgAdvisees)
                ->description("{$overloaded} faculty at 5+ advisees")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overloaded > 0 ? 'danger' : 'success'),
        ];
    }
}
