<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MilestoneTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $milestones = [
            ['name' => 'Coursework Completed', 'category' => 'coursework', 'sort_order' => 1, 'applies_to_all_programs' => true, 'is_required' => true],
            ['name' => 'Comprehensive Examination Passed', 'category' => 'examination', 'sort_order' => 2, 'applies_to_all_programs' => true, 'is_required' => true],
            ['name' => 'Thesis/Dissertation Proposal Approved', 'category' => 'research', 'sort_order' => 3, 'applies_to_all_programs' => true, 'is_required' => true],
            ['name' => 'Proposal Defense Passed', 'category' => 'defense', 'sort_order' => 4, 'applies_to_all_programs' => true, 'is_required' => true],
            ['name' => 'Final Defense Passed', 'category' => 'defense', 'sort_order' => 5, 'applies_to_all_programs' => true, 'is_required' => true],
            ['name' => 'Publication Requirement Met', 'category' => 'publication', 'sort_order' => 6, 'applies_to_all_programs' => true, 'is_required' => true],
            ['name' => 'Graduation Clearance Completed', 'category' => 'other', 'sort_order' => 7, 'applies_to_all_programs' => true, 'is_required' => true],
        ];

        foreach ($milestones as $milestone) {
            DB::table('milestone_templates')->updateOrInsert(
            ['name' => $milestone['name'], 'program_id' => null],
                array_merge($milestone, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // PhD-DVST-R specific milestone
        $phdDvstRId = DB::table('programs')->where('code', 'PhD-DVST-R')->value('id');
        if ($phdDvstRId) {
            DB::table('milestone_templates')->updateOrInsert(
            ['name' => 'Journal Article Published/Accepted', 'program_id' => $phdDvstRId],
            [
                'category' => 'publication',
                'sort_order' => 8,
                'applies_to_all_programs' => false,
                'is_required' => true,
                'degree_level' => 'doctorate',
                'created_at' => now(),
                'updated_at' => now(),
            ]
            );
        }
    }
}
