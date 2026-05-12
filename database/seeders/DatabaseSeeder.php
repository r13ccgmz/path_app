<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            ProgramSeeder::class,
            ProgramMajorSeeder::class,
            CognateFieldSeeder::class,
            AcademicYearSeeder::class,
            MilestoneTemplateSeeder::class,
            UserSeeder::class,
            ShieldSeeder::class,
            CsvCourseSeeder::class, // Course import MUST run after programs, cognates, etc.
            ProgramRequirementsSeeder::class,
        ]);
    }
}
