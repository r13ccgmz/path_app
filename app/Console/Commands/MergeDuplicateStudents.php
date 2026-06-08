<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\StudentProgram;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class MergeDuplicateStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:merge-duplicate-students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merges duplicate student records based on identical surname and given_name.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to merge duplicate student records...');

        // Find names that appear more than once in the students table
        $duplicates = DB::table('students')
            ->select('surname', 'given_name', DB::raw('COUNT(*) as count'))
            ->groupBy('surname', 'given_name')
            ->having('count', '>', 1)
            ->get();

        $this->info("Found {$duplicates->count()} names with duplicate records.");

        $mergedCount = 0;

        foreach ($duplicates as $duplicate) {
            $records = Student::where('surname', $duplicate->surname)
                ->where('given_name', $duplicate->given_name)
                ->get();

            // Find the primary record (the one with a valid student number)
            $primary = null;
            $others = [];

            foreach ($records as $record) {
                if (!empty($record->student_number) && !str_starts_with($record->student_number, 'TEMP-')) {
                    if (!$primary) {
                        $primary = $record;
                    } else {
                        // More than one valid student number — these are DIFFERENT PEOPLE.
                        // Do NOT add to $others; they must not be merged.
                    }
                } else {
                    $others[] = $record;
                }
            }

            // If we don't have a clear primary with a valid ID, just pick the first one as primary
            if (!$primary) {
                $primary = $records->first();
                // Only merge records that have NO valid student number (empty or TEMP-)
                $others = $records->where('id', '!=', $primary->id)
                    ->filter(fn ($r) => empty($r->student_number) || str_starts_with($r->student_number, 'TEMP-'))
                    ->values();
            }

            // Safety: skip entirely if there's nothing to merge
            if (empty($others) || collect($others)->isEmpty()) {
                continue;
            }

            foreach ($others as $other) {
                DB::transaction(function () use ($primary, $other) {
                    // 1. Move Programs
                    $programs = StudentProgram::where('student_id', $other->id)->get();
                    foreach ($programs as $prog) {
                        // Ensure the primary doesn't already have this program+major combo
                        $exists = StudentProgram::where('student_id', $primary->id)
                            ->where('program_id', $prog->program_id)
                            ->where('program_major_id', $prog->program_major_id)
                            ->exists();
                        if (!$exists) {
                            $prog->update(['student_id' => $primary->id]);
                        } else {
                            $prog->delete(); // Delete duplicate program link
                        }
                    }

                    // 2. Move Enrollments
                    $enrollments = StudentEnrollment::where('student_id', $other->id)->get();
                    foreach ($enrollments as $enr) {
                        $exists = StudentEnrollment::where('student_id', $primary->id)
                            ->where('course_id', $enr->course_id)
                            ->where('semester_id', $enr->semester_id)
                            ->exists();
                        if (!$exists) {
                            // Update student_program_id if necessary
                            $newSpId = null;
                            if ($enr->student_program_id) {
                                $oldSp = StudentProgram::find($enr->student_program_id);
                                if ($oldSp) {
                                    $newSp = StudentProgram::where('student_id', $primary->id)
                                        ->where('program_id', $oldSp->program_id)
                                        ->where('program_major_id', $oldSp->program_major_id)
                                        ->first();
                                    if ($newSp) {
                                        $newSpId = $newSp->id;
                                    }
                                }
                            }
                            $enr->update([
                                'student_id' => $primary->id,
                                'student_program_id' => $newSpId ?? $enr->student_program_id
                            ]);
                        } else {
                            $enr->delete(); // Delete duplicate enrollment
                        }
                    }

                    // 3. Move other related data if tables exist
                    $tablesToMove = [
                        'student_committee_members',
                        'academic_outputs',
                        'student_milestones'
                    ];

                    foreach ($tablesToMove as $table) {
                        if (DB::getSchemaBuilder()->hasTable($table)) {
                            DB::table($table)->where('student_id', $other->id)->update(['student_id' => $primary->id]);
                        }
                    }

                    // 4. Delete the duplicate student
                    $other->delete();
                });

                $mergedCount++;
                $this->line("Merged duplicate for: {$primary->surname}, {$primary->given_name}");
            }
        }

        $this->info("Merging complete! Removed {$mergedCount} duplicate records.");
    }
}
