<?php

namespace Database\Seeders;

use App\Enums\CourseType;
use App\Models\Course;
use App\Models\Program;
use App\Models\ProgramCourse;
use Illuminate\Database\Seeder;

/**
 * Fix-up seeder: reads the CSV and sets the correct course_type
 * on each program_courses record (per-program, not global).
 */
class FixProgramCourseTypesSeeder extends Seeder
{
    private array $programMap = [
        'Doctor of Philosophy in Extension Education' => 'PhD-EE',
        'Doctor of Philosophy in Community Development' => 'PhD-CD',
        'Doctor of Philosophy in Development Studies' => 'PhD-DVST',
        'Doctor of Philosophy in Development Studies (by Research)' => 'PhD-DVST-R',
        'Master of Science in Extension Education' => 'MS-EE',
        'Master of Science in Community Development' => 'MS-CD',
        'Master of Science in Development Management and Governance' => 'MSDMG',
        'Master in Development Management and Governance' => 'MDMG',
        'Master in Public Affairs' => 'MPAf',
    ];

    private array $courseTypeMap = [
        'Core' => CourseType::Core,
        'Prescribed' => CourseType::Prescribed,
        'Major' => CourseType::Major,
        'Specialization' => CourseType::Major,
        'Cognate' => CourseType::Cognate,
        'Elective' => CourseType::Elective,
        'Thesis_Dissertation' => CourseType::ThesisDissertation,
        'Field_Study' => CourseType::FieldStudy,
        'Seminar' => CourseType::Seminar,
    ];

    public function run(): void
    {
        $csvPath = base_path('../data/List of Courses.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("CSV file not found: {$csvPath}");
            return;
        }

        $this->command->info('Fixing course_type on program_courses from CSV...');

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);
        $updated = 0;
        $skipped = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), null);
            }
            $row = array_combine($header, array_slice($data, 0, count($header)));

            $courseCode = trim($row['Course Code'] ?? '');
            $programName = trim($row['Degree/Program'] ?? '');
            $courseTypeStr = trim($row['Course Type'] ?? '');

            // Skip empty/null course codes
            if (! $courseCode || $courseCode === 'NULL') {
                $skipped++;
                continue;
            }

            $programCode = $this->programMap[$programName] ?? null;
            if (! $programCode) {
                $skipped++;
                continue;
            }

            $courseType = $this->courseTypeMap[$courseTypeStr] ?? null;
            if (! $courseType) {
                $skipped++;
                continue;
            }

            // Find program and course
            $program = Program::where('code', $programCode)->first();
            $course = Course::where('course_code', $courseCode)->first();

            if (! $program || ! $course) {
                $skipped++;
                continue;
            }

            // Update the course_type on ALL matching pivot records for this program+course
            $count = ProgramCourse::where('program_id', $program->id)
                ->where('course_id', $course->id)
                ->update(['course_type' => $courseType->value]);

            if ($count > 0) {
                $updated += $count;
            }
        }

        fclose($handle);

        $this->command->info("  Updated {$updated} program-course mappings with correct course_type.");
        $this->command->info("  Skipped {$skipped} rows (empty/unmatched).");
    }
}
