<?php

namespace App\Console\Commands;

use App\Models\Enrollee;
use App\Models\Graduate;
use App\Models\Student;
use App\Support\NameNormalizer;
use App\Support\SemesterNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeExistingData extends Command
{
    protected $signature = 'path:normalize-data
                            {--names : Normalize name capitalization}
                            {--semesters : Normalize graduate semester values}
                            {--degrees : Normalize graduate degree abbreviations}
                            {--all : Run all normalizations}
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Retroactively normalize existing data (names, semesters, degrees) in the database.';

    public function handle(): int
    {
        $runAll = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN — No changes will be saved.');
        }

        if ($runAll || $this->option('names')) {
            $this->normalizeNames($dryRun);
        }

        if ($runAll || $this->option('semesters')) {
            $this->normalizeSemesters($dryRun);
        }

        if ($runAll || $this->option('degrees')) {
            $this->normalizeDegrees($dryRun);
        }

        if (!$runAll && !$this->option('names') && !$this->option('semesters') && !$this->option('degrees')) {
            $this->warn('No normalization type specified. Use --all, --names, --semesters, or --degrees.');
            return 1;
        }

        $this->newLine();
        $this->info('✅ Normalization complete.' . ($dryRun ? ' (DRY RUN — nothing was saved)' : ''));
        return 0;
    }

    private function normalizeNames(bool $dryRun): void
    {
        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('  Normalizing Name Capitalization');
        $this->info('═══════════════════════════════════════');

        // 1. Enrollees
        $enrolleeCount = 0;
        $enrollees = Enrollee::whereRaw('last_name = UPPER(last_name)')
            ->orWhereRaw('first_name = UPPER(first_name)')
            ->get();

        foreach ($enrollees as $enrollee) {
            $newLast = NameNormalizer::normalize($enrollee->last_name);
            $newFirst = NameNormalizer::normalize($enrollee->first_name);
            $newMiddle = NameNormalizer::normalize($enrollee->middle_name);

            $changed = ($newLast !== $enrollee->last_name)
                    || ($newFirst !== $enrollee->first_name)
                    || ($newMiddle !== $enrollee->middle_name);

            if ($changed) {
                $enrolleeCount++;
                if ($this->output->isVerbose()) {
                    $this->line("  Enrollee #{$enrollee->id}: {$enrollee->last_name}, {$enrollee->first_name} → {$newLast}, {$newFirst}");
                }
                if (!$dryRun) {
                    $enrollee->update([
                        'last_name' => $newLast,
                        'first_name' => $newFirst,
                        'middle_name' => $newMiddle,
                    ]);
                }
            }
        }
        $this->info("  📋 Enrollees: {$enrolleeCount} names normalized");

        // 2. Graduates
        $graduateCount = 0;
        $graduates = Graduate::whereRaw('name = UPPER(name)')->get();

        foreach ($graduates as $graduate) {
            $newName = NameNormalizer::normalizeFullName($graduate->name);
            $newChair = NameNormalizer::normalize($graduate->chair);
            $newCoChair = NameNormalizer::normalize($graduate->co_chair);
            $newM1 = NameNormalizer::normalize($graduate->member1);
            $newM2 = NameNormalizer::normalize($graduate->member2);
            $newM3 = NameNormalizer::normalize($graduate->member3);
            $newM4 = NameNormalizer::normalize($graduate->member4);
            $newM5 = NameNormalizer::normalize($graduate->member5);
            $newMajor = NameNormalizer::normalize($graduate->major_field_raw);

            $changed = ($newName !== $graduate->name);

            if ($changed) {
                $graduateCount++;
                if ($this->output->isVerbose()) {
                    $this->line("  Graduate #{$graduate->id}: {$graduate->name} → {$newName}");
                }
                if (!$dryRun) {
                    $graduate->update([
                        'name' => $newName,
                        'chair' => $newChair,
                        'co_chair' => $newCoChair,
                        'member1' => $newM1,
                        'member2' => $newM2,
                        'member3' => $newM3,
                        'member4' => $newM4,
                        'member5' => $newM5,
                        'major_field_raw' => $newMajor,
                    ]);
                }
            }
        }
        $this->info("  🎓 Graduates: {$graduateCount} names normalized");

        // 3. Students
        $studentCount = 0;
        $students = Student::whereRaw('surname = UPPER(surname)')
            ->orWhereRaw('given_name = UPPER(given_name)')
            ->get();

        foreach ($students as $student) {
            $newSurname = NameNormalizer::normalize($student->surname);
            $newGiven = NameNormalizer::normalize($student->given_name);
            $newMiddle = NameNormalizer::normalize($student->middle_name);
            $newFull = Student::buildFullName($newSurname, $newGiven, $newMiddle);

            $changed = ($newSurname !== $student->surname)
                    || ($newGiven !== $student->given_name)
                    || ($newMiddle !== $student->middle_name);

            if ($changed) {
                $studentCount++;
                if ($this->output->isVerbose()) {
                    $this->line("  Student #{$student->id}: {$student->full_name} → {$newFull}");
                }
                if (!$dryRun) {
                    $student->update([
                        'surname' => $newSurname,
                        'given_name' => $newGiven,
                        'middle_name' => $newMiddle,
                        'full_name' => $newFull,
                    ]);
                }
            }
        }
        $this->info("  👤 Students: {$studentCount} names normalized");
    }

    private function normalizeSemesters(bool $dryRun): void
    {
        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('  Normalizing Semester Values');
        $this->info('═══════════════════════════════════════');

        $count = 0;
        $graduates = Graduate::whereNotNull('semester_graduated')
            ->where('semester_graduated', '!=', '')
            ->get();

        foreach ($graduates as $graduate) {
            $raw = $graduate->semester_graduated;

            // Skip if it's already a valid numeric term code
            if (is_numeric($raw)) {
                continue;
            }

            $normalized = SemesterNormalizer::normalize($raw);

            if ($normalized !== $raw) {
                $count++;
                if ($this->output->isVerbose()) {
                    $this->line("  Graduate #{$graduate->id} ({$graduate->name}): '{$raw}' → '{$normalized}'");
                }
                if (!$dryRun) {
                    $graduate->update(['semester_graduated' => $normalized]);
                }
            }
        }

        $this->info("  📅 Graduates: {$count} semester values normalized");
    }

    private function normalizeDegrees(bool $dryRun): void
    {
        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('  Normalizing Degree Abbreviations');
        $this->info('═══════════════════════════════════════');

        $degreeMap = [
            'PHD' => 'PhD', 'PH.D' => 'PhD', 'PH.D.' => 'PhD',
            'MS' => 'MS', 'M.S' => 'MS', 'M.S.' => 'MS',
            'MA' => 'MA', 'M.A' => 'MA', 'M.A.' => 'MA',
            'MPA' => 'MPA', 'MPAF' => 'MPAF', 'MPAEM' => 'MPAEM',
            'MDMG' => 'MDMG', 'DMG' => 'MDMG', 'DPA' => 'DPA',
            'MSDMG' => 'MSDMG', 'MSCD' => 'MSCD', 'MSEE' => 'MSEE',
        ];

        $count = 0;
        $graduates = Graduate::whereNotNull('degree')
            ->where('degree', '!=', '')
            ->get();

        foreach ($graduates as $graduate) {
            $upper = strtoupper(trim($graduate->degree));
            if (isset($degreeMap[$upper]) && $degreeMap[$upper] !== $graduate->degree) {
                $count++;
                if ($this->output->isVerbose()) {
                    $this->line("  Graduate #{$graduate->id}: '{$graduate->degree}' → '{$degreeMap[$upper]}'");
                }
                if (!$dryRun) {
                    $graduate->update(['degree' => $degreeMap[$upper]]);
                }
            }
        }

        $this->info("  🎓 Graduates: {$count} degree abbreviations normalized");
    }
}
