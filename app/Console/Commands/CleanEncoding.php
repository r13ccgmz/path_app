<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanEncoding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-encoding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up invalid encoding characters (e.g.  to Ñ) across the system.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting encoding cleanup...');

        $badChar = "\xEF\xBF\xBD";
        $goodChar = 'Ñ';
        
        // 1. Clean Enrollees
        $this->info('Cleaning enrollees...');
        $enrolleesFixed = 0;
        DB::table('enrollees')->orderBy('id')->chunk(500, function ($records) use ($badChar, $goodChar, &$enrolleesFixed) {
            foreach ($records as $record) {
                $updates = [];
                if (str_contains($record->last_name ?? '', $badChar)) $updates['last_name'] = str_replace($badChar, $goodChar, $record->last_name);
                if (str_contains($record->first_name ?? '', $badChar)) $updates['first_name'] = str_replace($badChar, $goodChar, $record->first_name);
                if (str_contains($record->middle_name ?? '', $badChar)) $updates['middle_name'] = str_replace($badChar, $goodChar, $record->middle_name);

                if (!empty($updates)) {
                    DB::table('enrollees')->where('id', $record->id)->update($updates);
                    $enrolleesFixed++;
                }
            }
        });
        $this->line("Fixed {$enrolleesFixed} enrollees.");

        // 2. Clean Students
        $this->info('Cleaning students...');
        $studentsFixed = 0;
        DB::table('students')->orderBy('id')->chunk(500, function ($records) use ($badChar, $goodChar, &$studentsFixed) {
            foreach ($records as $record) {
                $updates = [];
                if (str_contains($record->surname ?? '', $badChar)) $updates['surname'] = str_replace($badChar, $goodChar, $record->surname);
                if (str_contains($record->given_name ?? '', $badChar)) $updates['given_name'] = str_replace($badChar, $goodChar, $record->given_name);
                if (str_contains($record->middle_name ?? '', $badChar)) $updates['middle_name'] = str_replace($badChar, $goodChar, $record->middle_name);
                if (str_contains($record->full_name ?? '', $badChar)) $updates['full_name'] = str_replace($badChar, $goodChar, $record->full_name);

                if (!empty($updates)) {
                    DB::table('students')->where('id', $record->id)->update($updates);
                    $studentsFixed++;
                }
            }
        });
        $this->line("Fixed {$studentsFixed} students.");

        // 3. Clean Graduates
        $this->info('Cleaning graduates...');
        $graduatesFixed = 0;
        DB::table('graduates')->orderBy('id')->chunk(500, function ($records) use ($badChar, $goodChar, &$graduatesFixed) {
            foreach ($records as $record) {
                $updates = [];
                if (str_contains($record->student_name ?? '', $badChar)) $updates['student_name'] = str_replace($badChar, $goodChar, $record->student_name);
                
                if (!empty($updates)) {
                    DB::table('graduates')->where('id', $record->id)->update($updates);
                    $graduatesFixed++;
                }
            }
        });
        $this->line("Fixed {$graduatesFixed} graduates.");

        $this->info('Encoding cleanup completed successfully.');
    }
}
