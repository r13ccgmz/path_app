<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Enrollee;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class NormalizeStudentNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normalize-student-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fills in missing student numbers in the enrollees table using existing data or generates temporary IDs.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting student number normalization...');

        // Find distinct names that have empty student numbers
        $missingRecords = DB::table('enrollees')
            ->select('last_name', 'first_name')
            ->where(function ($query) {
                $query->whereNull('student_number')
                      ->orWhere('student_number', '');
            })
            ->distinct()
            ->get();

        $this->info("Found {$missingRecords->count()} unique names with missing student numbers.");

        $updatedCount = 0;
        $tempCount = 0;

        foreach ($missingRecords as $record) {
            $lastName = $record->last_name;
            $firstName = $record->first_name;

            // Search for a valid student number for this name
            $existingValid = DB::table('enrollees')
                ->where('last_name', $lastName)
                ->where('first_name', $firstName)
                ->whereNotNull('student_number')
                ->where('student_number', '!=', '')
                ->first();

            $newStudentNumber = null;

            if ($existingValid) {
                $newStudentNumber = $existingValid->student_number;
                $updatedCount++;
            } else {
                // Generate temporary ID
                $slug = Str::slug($lastName . '-' . $firstName, '');
                $newStudentNumber = 'TEMP-' . strtoupper(substr($slug, 0, 15));
                $tempCount++;
            }

            // Update all empty records for this person
            DB::table('enrollees')
                ->where('last_name', $lastName)
                ->where('first_name', $firstName)
                ->where(function ($query) {
                    $query->whereNull('student_number')
                          ->orWhere('student_number', '');
                })
                ->update(['student_number' => $newStudentNumber]);
                
            $this->line("Processed: {$lastName}, {$firstName} -> {$newStudentNumber}");
        }

        $this->info("Normalization complete! Backfilled {$updatedCount} from existing records, created {$tempCount} temporary IDs.");
    }
}
