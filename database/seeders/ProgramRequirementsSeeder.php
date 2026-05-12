<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\ProgramRequirement;

class ProgramRequirementsSeeder extends Seeder
{
    /**
     * Seed program requirements from the List of Program Requirements CSV.
     * Sets total_units_required and min_units_per_type on programs table,
     * AND creates program_requirements text rows for the Curriculum Map UI.
     */
    public function run(): void
    {
        $requirements = [
            'PhD-EE' => [
                'total_units_required' => 45,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Core' => 14,
                    'Specialization' => 6,
                    'Seminar' => 1,
                    'Elective' => 3,
                    'Cognate' => 9,
                    'Dissertation' => 12,
                ],
            ],
            'PhD-CD' => [
                'total_units_required' => 45,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Core' => 14,
                    'Specialization' => 6,
                    'Seminar' => 1,
                    'Elective' => 3,
                    'Cognate' => 9,
                    'Dissertation' => 12,
                ],
            ],
            'PhD-DVST' => [
                'total_units_required' => 49,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Core' => 12,
                    'Specialization' => 12,
                    'Cognate' => 12,
                    'Seminar' => 1,
                    'Dissertation' => 12,
                ],
            ],
            'PhD-DVST-R' => [
                'total_units_required' => 15,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Core' => 15,
                ],
            ],
            'MS-EE' => [
                'total_units_required' => 37,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Core' => 12,
                    'Specialization' => 6,
                    'Seminar' => 1,
                    'Elective' => 3,
                    'Cognate' => 9,
                    'Thesis' => 6,
                ],
            ],
            'MS-CD' => [
                'total_units_required' => 37,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Core' => 12,
                    'Specialization' => 6,
                    'Seminar' => 1,
                    'Elective' => 3,
                    'Cognate' => 9,
                    'Thesis' => 6,
                ],
            ],
            'MSDMG' => [
                'total_units_required' => 38,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Prescribed' => 19,
                    'Major' => 9,
                    'Elective' => 3,
                    'Seminar' => 1,
                    'Thesis' => 6,
                ],
            ],
            'MDMG' => [
                'total_units_required' => 31,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Prescribed' => 15,
                    'Major' => 9,
                    'Elective' => 3,
                    'Seminar' => 1,
                    'Field Study' => 3,
                ],
            ],
            'MPAf' => [
                'total_units_required' => 31,
                'max_residency_years' => 5,
                'min_units_per_type' => [
                    'Core' => 13,
                    'Specialization' => 8,
                    'Elective' => 6,
                    'Field Study' => 3,
                    'Seminar' => 1,
                ],
            ],
        ];

        foreach ($requirements as $code => $data) {
            $program = Program::where('code', $code)->first();
            if ($program) {
                $program->update($data);
                $this->command->info("Updated requirements for {$code}: {$program->name}");

                // Generate program_requirements text rows from min_units_per_type
                $this->generateRequirementText($program, $data);
            } else {
                $this->command->warn("Program {$code} not found, skipping.");
            }
        }
    }

    /**
     * Create program_requirements text rows for a program.
     */
    private function generateRequirementText(Program $program, array $data): void
    {
        $sortOrder = 0;

        // Generate requirement rows for each course type
        if (!empty($data['min_units_per_type'])) {
            foreach ($data['min_units_per_type'] as $type => $units) {
                $unitWord = $units === 1 ? 'unit' : 'units';
                $text = "Minimum of {$units} {$unitWord} of {$type} Courses";

                ProgramRequirement::firstOrCreate(
                    [
                        'program_id' => $program->id,
                        'requirement_text' => $text,
                    ],
                    [
                        'sort_order' => $sortOrder++,
                    ]
                );
            }
        }

        // Total units requirement
        if (!empty($data['total_units_required'])) {
            $totalText = "Total of {$data['total_units_required']} units required";
            ProgramRequirement::firstOrCreate(
                [
                    'program_id' => $program->id,
                    'requirement_text' => $totalText,
                ],
                [
                    'sort_order' => $sortOrder++,
                ]
            );
        }

        // Residency requirement
        if (!empty($data['max_residency_years'])) {
            $residencyText = "Maximum residency of {$data['max_residency_years']} years";
            ProgramRequirement::firstOrCreate(
                [
                    'program_id' => $program->id,
                    'requirement_text' => $residencyText,
                ],
                [
                    'sort_order' => $sortOrder++,
                ]
            );
        }

        $this->command->info("  → Created " . ($sortOrder) . " requirement text rows for {$program->code}");
    }
}
