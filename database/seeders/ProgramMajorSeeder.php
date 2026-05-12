<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramMajorSeeder extends Seeder
{
    public function run(): void
    {
        $specializations = [
            'PhD-DVST' => [
                'Agriculture, Food and Nutrition Security',
                'Agrarian and Rural Development Studies',
                'Education and Development',
                'Natural Resource Management',
                'Population, Gender and Development Studies',
            ],
            'MSDMG' => [
                'Governance of Microfinance and Microinsurance Institutions',
                'Local Governance and Development',
                'Organizational and Institutional Development',
                'Program Management',
            ],
            'MDMG' => [
                'Governance of Microfinance and Microinsurance Institutions',
                'Local Governance and Development',
                'Organizational and Institutional Development',
                'Program Management',
                'Public Finance Management and Governance',
            ],
            'MPAf' => [
                'Agrarian and Rurban Development Studies',
                'Education Management',
                'Strategic Planning and Public Policy',
            ],
        ];

        foreach ($specializations as $programCode => $majors) {
            $programId = DB::table('programs')->where('code', $programCode)->value('id');

            if (!$programId) {
                continue;
            }

            foreach ($majors as $majorName) {
                DB::table('program_majors')->updateOrInsert(
                ['program_id' => $programId, 'name' => $majorName],
                ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
