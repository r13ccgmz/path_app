<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'IGRD', 'name' => 'Institute for Governance and Rural Development'],
            ['code' => 'CISC', 'name' => 'Community Innovations Studies Center'],
            ['code' => 'CSPPS', 'name' => 'Center for Strategic Planning and Policy Studies'],
            ['code' => 'KMO', 'name' => 'Knowledge Management Office'],
            ['code' => 'DO', 'name' => 'Deans Office'],
        ];

        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(
            ['code' => $unit['code']],
                array_merge($unit, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
