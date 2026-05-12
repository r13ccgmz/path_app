<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CognateFieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            'Strategic Planning and Policy Studies',
            'Economics',
            'Development Communication',
            'Development Management and Governance',
            'Political Science',
            'Sociology',
            'Community Education / Comparative and International Education',
        ];

        foreach ($fields as $field) {
            DB::table('cognate_fields')->updateOrInsert(
            ['name' => $field],
            ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
