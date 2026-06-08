<?php

namespace App\Console\Commands;

use App\Models\Enrollee;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProgram;
use Illuminate\Console\Command;

class SyncStudentStatuses extends Command
{
    protected $signature = 'students:sync-statuses {--dry-run : Show what would change without making changes}';
    protected $description = 'Sync student statuses: mark unenrolled active students as inactive, reactivate enrolled inactive students, identify candidates for graduation.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $currentSem = Semester::where('is_current', true)->first();

        if (!$currentSem) {
            $this->error('No current semester is set. Please set one first.');
            return Command::FAILURE;
        }

        $termCode = $currentSem->term_code;
        $this->info("Current semester: [{$termCode}] {$currentSem->label}");
        if ($dryRun) $this->warn('DRY RUN — no changes will be made.');

        $inactiveCount = 0;
        $reactivateCount = 0;
        $candidateCount = 0;

        // 1. Mark active students without current-term enrollment as inactive
        $this->info('Checking active students for current-term enrollment...');
        $activeStudents = Student::where('student_status', 'active')->get();
        foreach ($activeStudents as $student) {
            $hasEnrollment = Enrollee::where('student_number', $student->student_number)
                ->where('term_id', $termCode)->exists();
            if (!$hasEnrollment) {
                if (!$dryRun) $student->update(['student_status' => 'inactive']);
                $this->line("  → {$student->full_name} ({$student->student_number}): active → inactive");
                $inactiveCount++;
            }
        }

        // 2. Reactivate inactive students with current-term enrollment
        $this->info('Checking inactive students for re-enrollment...');
        $inactiveStudents = Student::where('student_status', 'inactive')->get();
        foreach ($inactiveStudents as $student) {
            $hasEnrollment = Enrollee::where('student_number', $student->student_number)
                ->where('term_id', $termCode)->exists();
            if ($hasEnrollment) {
                if (!$dryRun) $student->update(['student_status' => 'active']);
                $this->line("  → {$student->full_name} ({$student->student_number}): inactive → active");
                $reactivateCount++;
            }
        }

        // 3. Identify candidates for graduation
        $this->info('Checking for candidates for graduation...');
        $potentialCandidates = Student::whereIn('student_status', ['active'])
            ->whereNotNull('program_id')
            ->with('program')
            ->get();
        foreach ($potentialCandidates as $student) {
            $program = $student->program;
            if (!$program || !$program->total_units_required) continue;
            $totalRequired = $program->total_units_required;
            if ($totalRequired <= 0) continue;

            $totalEarned = StudentEnrollment::where('student_id', $student->id)->sum('units_earned');
            if ($totalEarned >= $totalRequired) {
                if (!$dryRun) {
                    $student->update(['student_status' => 'candidate']);
                    StudentProgram::where('student_id', $student->id)
                        ->where('program_id', $student->program_id)
                        ->where('status', 'active')
                        ->update(['status' => 'candidate']);
                }
                $this->line("  → {$student->full_name}: active → candidate ({$totalEarned}/{$totalRequired} units)");
                $candidateCount++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$inactiveCount} marked inactive, {$reactivateCount} reactivated, {$candidateCount} candidates.");
        if ($dryRun) $this->warn('This was a dry run. Run without --dry-run to apply changes.');

        return Command::SUCCESS;
    }
}
