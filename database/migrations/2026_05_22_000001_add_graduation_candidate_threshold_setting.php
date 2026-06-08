<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->insertOrIgnore([
            'key' => 'graduation_candidate_threshold',
            'value' => '100',
            'type' => 'integer',
            'group' => 'graduation',
            'label' => 'Graduation Candidate Threshold (%)',
            'description' => 'Students at or above this completion percentage are flagged as candidates for graduation.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'graduation_candidate_threshold')->delete();
    }
};
