<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->insertOrIgnore([
            [
                'key' => 'student_inactivity_semesters',
                'value' => '3',
                'type' => 'integer',
                'group' => 'student_sync',
                'label' => 'Inactivity Threshold (Semesters)',
                'description' => 'Mark students as inactive if they have not enrolled for this many consecutive semesters.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'student_inactive_target_status',
                'value' => 'inactive',
                'type' => 'string',
                'group' => 'student_sync',
                'label' => 'Inactive Target Status',
                'description' => 'The student status to assign when the inactivity threshold is exceeded.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'student_inactivity_semesters',
            'student_inactive_target_status',
        ])->delete();
    }
};
