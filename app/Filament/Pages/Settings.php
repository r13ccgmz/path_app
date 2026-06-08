<?php

namespace App\Filament\Pages;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Models\SystemSetting;
use App\Models\Student;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;

class Settings extends Page
{
    use HasPageShield;
    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 9;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'System Settings';

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.pages.settings';

    // Enrollment settings
    public ?int $full_time_units_threshold = 9;

    // Graduation settings
    public ?int $graduation_candidate_threshold = 100;

    // Student sync settings
    public ?int $student_inactivity_semesters = 3;
    public ?string $student_inactive_target_status = 'inactive';
    public bool $exclude_candidates_from_inactivity = true;

    public function mount(): void
    {
        $this->full_time_units_threshold = (int) SystemSetting::get('full_time_units_threshold', 9);
        $this->graduation_candidate_threshold = (int) SystemSetting::get('graduation_candidate_threshold', 100);
        $this->student_inactivity_semesters = (int) SystemSetting::get('student_inactivity_semesters', 3);
        $this->student_inactive_target_status = SystemSetting::get('student_inactive_target_status', 'inactive');
        $this->exclude_candidates_from_inactivity = (bool) SystemSetting::get('exclude_candidates_from_inactivity', true);
    }

    public function saveEnrollmentSettings(): void
    {
        $validated = $this->validate([
            'full_time_units_threshold' => 'required|integer|min:1|max:30',
        ]);

        SystemSetting::set('full_time_units_threshold', $validated['full_time_units_threshold']);

        cache()->forget('system_setting:full_time_units_threshold');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        Notification::make()
            ->title('Settings Updated')
            ->body("Full-time threshold set to {$validated['full_time_units_threshold']} units. All dashboard caches have been refreshed.")
            ->success()
            ->duration(3000)
            ->send();
    }

    public function saveGraduationSettings(): void
    {
        $validated = $this->validate([
            'graduation_candidate_threshold' => 'required|integer|min:50|max:100',
        ]);

        SystemSetting::set('graduation_candidate_threshold', $validated['graduation_candidate_threshold']);
        cache()->forget('system_setting:graduation_candidate_threshold');

        // Auto-sync candidate statuses when threshold changes
        $result = $this->syncCandidateStatuses();

        Notification::make()
            ->title('Graduation Settings Updated')
            ->body("Candidate threshold set to {$validated['graduation_candidate_threshold']}%. {$result['promoted']} student(s) marked as candidates, {$result['demoted']} reverted to active.")
            ->success()
            ->duration(5000)
            ->send();
    }

    /**
     * Sync candidate statuses based on the graduation threshold.
     * Sets student_programs.status = 'candidate' for students who meet the threshold.
     * Reverts students who no longer meet the threshold back to 'active'.
     */
    public function syncCandidateStatuses(): array
    {
        $threshold = (int) SystemSetting::get('graduation_candidate_threshold', 100);
        $thresholdDecimal = $threshold / 100;

        // Promote: active → candidate (students who meet the threshold)
        $promoted = DB::table('student_programs')
            ->join('programs', 'student_programs.program_id', '=', 'programs.id')
            ->where('programs.total_units_required', '>', 0)
            ->where('student_programs.status', 'active')
            ->whereRaw(
                '(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) >= (programs.total_units_required * ?)',
                ['completed', $thresholdDecimal]
            )
            ->update(['student_programs.status' => 'candidate']);

        // Demote: candidate → active (students who no longer meet the threshold)
        $demoted = DB::table('student_programs')
            ->join('programs', 'student_programs.program_id', '=', 'programs.id')
            ->where('programs.total_units_required', '>', 0)
            ->where('student_programs.status', 'candidate')
            ->whereRaw(
                '(SELECT COALESCE(SUM(se.units_earned), 0) FROM student_enrollments se WHERE se.student_program_id = student_programs.id AND se.status = ?) < (programs.total_units_required * ?)',
                ['completed', $thresholdDecimal]
            )
            ->update(['student_programs.status' => 'active']);

        // Also update students.student_status to match
        // Get all student IDs that have at least one 'candidate' student_program
        $candidateStudentIds = DB::table('student_programs')
            ->where('status', 'candidate')
            ->pluck('student_id')
            ->unique();

        if ($candidateStudentIds->isNotEmpty()) {
            Student::whereIn('id', $candidateStudentIds)
                ->where('student_status', 'active')
                ->update(['student_status' => 'candidate']);
        }

        // Revert students whose ALL student_programs are no longer 'candidate'
        $nonCandidateStudentIds = Student::where('student_status', 'candidate')
            ->whereDoesntHave('studentPrograms', fn ($q) => $q->where('status', 'candidate'))
            ->pluck('id');

        if ($nonCandidateStudentIds->isNotEmpty()) {
            Student::whereIn('id', $nonCandidateStudentIds)
                ->update(['student_status' => 'active']);
        }

        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        return ['promoted' => $promoted, 'demoted' => $demoted];
    }

    public function saveStudentSyncSettings(): void
    {
        $validated = $this->validate([
            'student_inactivity_semesters' => 'required|integer|min:1|max:20',
            'student_inactive_target_status' => 'required|string|in:inactive,absent-without-official-leave,on-leave',
        ]);

        SystemSetting::set('student_inactivity_semesters', $validated['student_inactivity_semesters']);
        SystemSetting::set('student_inactive_target_status', $validated['student_inactive_target_status']);
        SystemSetting::set('exclude_candidates_from_inactivity', $this->exclude_candidates_from_inactivity ? '1' : '0');
        cache()->forget('system_setting:student_inactivity_semesters');
        cache()->forget('system_setting:student_inactive_target_status');
        cache()->forget('system_setting:exclude_candidates_from_inactivity');

        Notification::make()
            ->title('Student Sync Settings Updated')
            ->body("Inactivity threshold: {$validated['student_inactivity_semesters']} semesters → Status: " . ucwords(str_replace('-', ' ', $validated['student_inactive_target_status'])))
            ->success()
            ->duration(3000)
            ->send();
    }

    public function syncStudentStatuses(): void
    {
        $threshold = (int) SystemSetting::get('student_inactivity_semesters', 3);
        $targetStatus = SystemSetting::get('student_inactive_target_status', 'inactive');
        $excludeCandidates = (bool) SystemSetting::get('exclude_candidates_from_inactivity', true);

        // Get the N most recent semesters
        $recentSemesters = Semester::orderByRaw('CAST(term_code AS UNSIGNED) DESC')
            ->take($threshold)
            ->pluck('id')
            ->toArray();

        if (empty($recentSemesters)) {
            Notification::make()
                ->title('No Semesters Found')
                ->body('Cannot determine recent semesters for sync. Please ensure semester data exists.')
                ->warning()
                ->send();
            return;
        }

        // Build the query for active students who have NOT enrolled in recent semesters
        $query = Student::where('student_status', 'active')
            ->whereDoesntHave('enrollees', function ($q) use ($recentSemesters) {
                $q->whereIn('term_id', function ($sub) use ($recentSemesters) {
                    $sub->select('term_code')
                        ->from('semesters')
                        ->whereIn('id', $recentSemesters);
                });
            });

        // Exclude candidates from inactivity sync if the setting is enabled
        if ($excludeCandidates) {
            $query->where('student_status', '!=', 'candidate');
        }

        $affectedCount = $query->update(['student_status' => $targetStatus]);

        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        $statusLabel = ucwords(str_replace('-', ' ', $targetStatus));
        $excludeNote = $excludeCandidates ? ' Graduation candidates were excluded.' : '';
        Notification::make()
            ->title('Student Status Sync Complete')
            ->body("{$affectedCount} student(s) marked as '{$statusLabel}' (no enrollment in the last {$threshold} semester(s)).{$excludeNote}")
            ->success()
            ->duration(5000)
            ->send();
    }
}
