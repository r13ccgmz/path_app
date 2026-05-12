<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['code' => 'PhD-EE', 'name' => 'Doctor of Philosophy in Extension Education', 'degree_level' => 'doctorate', 'total_units_required' => 45],
            ['code' => 'PhD-CD', 'name' => 'Doctor of Philosophy in Community Development', 'degree_level' => 'doctorate', 'total_units_required' => 45],
            ['code' => 'PhD-DVST', 'name' => 'Doctor of Philosophy in Development Studies', 'degree_level' => 'doctorate', 'total_units_required' => 49],
            ['code' => 'PhD-DVST-R', 'name' => 'Doctor of Philosophy in Development Studies (by Research)', 'degree_level' => 'doctorate', 'total_units_required' => 15],
            ['code' => 'MS-EE', 'name' => 'Master of Science in Extension Education', 'degree_level' => 'master_of_science', 'total_units_required' => 37],
            ['code' => 'MS-CD', 'name' => 'Master of Science in Community Development', 'degree_level' => 'master_of_science', 'total_units_required' => 37],
            ['code' => 'MSDMG', 'name' => 'Master of Science in Development Management and Governance', 'degree_level' => 'master_of_science', 'total_units_required' => 38],
            ['code' => 'MDMG', 'name' => 'Master in Development Management and Governance', 'degree_level' => 'master', 'total_units_required' => 31],
            ['code' => 'MPAf', 'name' => 'Master in Public Affairs', 'degree_level' => 'master', 'total_units_required' => 31],
        ];

        foreach ($programs as $program) {
            DB::table('programs')->updateOrInsert(
            ['code' => $program['code']],
                array_merge($program, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            );
        }
    }
}
