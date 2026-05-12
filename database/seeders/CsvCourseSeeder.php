<?php

namespace Database\Seeders;

use App\Enums\CourseType;
use App\Models\CognateField;
use App\Models\Course;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\ProgramMajor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CsvCourseSeeder extends Seeder
{
    /**
     * Map CSV program names → database program codes.
     */
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

    /**
     * Map CSV course type values → CourseType enum.
     */
    private array $courseTypeMap = [
        'Core' => CourseType::Core,
        'Prescribed' => CourseType::Prescribed,
        'Major' => CourseType::Major,
        'Specialization' => CourseType::Specialization,
        'Cognate' => CourseType::Cognate,
        'Elective' => CourseType::Elective,
        'Thesis_Dissertation' => CourseType::Thesis,
        'Thesis' => CourseType::Thesis,
        'Dissertation' => CourseType::Dissertation,
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

        $this->command->info('Importing courses from CSV...');

        $rows = $this->parseCsv($csvPath);
        $this->command->info("Parsed " . count($rows) . " rows from CSV.");

        // Ensure PhD-DVST (by Research) program exists
        $this->ensureResearchProgram();

        // Phase 1: Import cognate fields
        $cognateFields = $this->importCognateFields($rows);

        // Phase 2: Import unique courses (deduplicated by course_code)
        $courses = $this->importCourses($rows, $cognateFields);

        // Phase 3: Map courses to programs (+ specializations)
        $this->importProgramCourses($rows, $courses);

        $this->command->info("Import complete!");
        $this->command->info("  Cognate fields: " . count($cognateFields));
        $this->command->info("  Courses: " . count($courses));
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), null);
            }
            $row = array_combine($header, array_slice($data, 0, count($header)));
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function ensureResearchProgram(): void
    {
        if (! Program::where('code', 'PhD-DVST-R')->exists()) {
            Program::create([
                'code' => 'PhD-DVST-R',
                'name' => 'Doctor of Philosophy in Development Studies (by Research)',
                'degree_level' => 'doctorate',
                'is_active' => true,
            ]);
            $this->command->info("  Created PhD-DVST-R program.");
        }
    }

    private function importCognateFields(array $rows): array
    {
        $fields = [];

        foreach ($rows as $row) {
            $fieldName = trim($row['Cognate Field'] ?? '');
            if ($fieldName && $fieldName !== 'NULL' && ! isset($fields[$fieldName])) {
                $cf = CognateField::firstOrCreate(['name' => $fieldName]);
                $fields[$fieldName] = $cf->id;
            }
        }

        $this->command->info("  Imported " . count($fields) . " cognate fields.");
        return $fields;
    }

    private function importCourses(array $rows, array $cognateFields): array
    {
        $courses = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $courseCode = trim($row['Course Code'] ?? '');
            $courseName = trim($row['Course Name'] ?? '');
            $courseTypeStr = trim($row['Course Type'] ?? '');

            // Skip empty course codes, placeholder rows, and generic entries
            if (! $courseCode || $courseCode === 'NULL') {
                $skipped++;
                continue;
            }
            if (str_contains($courseName, 'Cognate Courses') || str_contains($courseName, 'Elective')) {
                if (! $courseCode || $courseCode === 'NULL') {
                    $skipped++;
                    continue;
                }
            }

            // Already processed — just update missing description/notes/prereq
            if (isset($courses[$courseCode])) {
                continue;
            }

            $course = Course::firstOrCreate(
                ['course_code' => $courseCode],
                [
                    'course_name' => $courseName,
                    'is_active' => true,
                ]
            );

            $courses[$courseCode] = $course->id;
        }

        $this->command->info("  Imported " . count($courses) . " unique courses (skipped {$skipped} generic/empty rows).");
        return $courses;
    }

    private function importProgramCourses(array $rows, array $courses): void
    {
        $cognateFields = CognateField::pluck('id', 'name')->toArray();
        $mappings = 0;

        foreach ($rows as $row) {
            $courseCode = trim($row['Course Code'] ?? '');
            if (! $courseCode || $courseCode === 'NULL' || ! isset($courses[$courseCode])) {
                continue;
            }

            $programName = trim($row['Degree/Program'] ?? '');
            $programCode = $this->programMap[$programName] ?? null;
            if (! $programCode) {
                continue;
            }

            $program = Program::where('code', $programCode)->first();
            if (! $program) {
                continue;
            }

            // Resolve specialization
            $majorId = null;
            $specName = $this->cleanNull($row['Specialization'] ?? '');
            if ($specName) {
                $major = ProgramMajor::where('program_id', $program->id)
                    ->where('name', $specName)
                    ->first();

                if (! $major) {
                    $major = ProgramMajor::create([
                        'program_id' => $program->id,
                        'name' => $specName,
                    ]);
                }
                $majorId = $major->id;
            }

            // Resolve cognate field
            $cognateFieldId = null;
            $fieldName = $this->cleanNull($row['Cognate Field'] ?? '');
            if ($fieldName && isset($cognateFields[$fieldName])) {
                $cognateFieldId = $cognateFields[$fieldName];
            }

            $courseTypeStr = trim($row['Course Type'] ?? '');
            $courseType = $this->courseTypeMap[$courseTypeStr] ?? null;

            if ($courseType === CourseType::Thesis && $program->degree_level === \App\Enums\DegreeLevel::Doctorate) {
                $courseType = CourseType::Dissertation;
            }

            // Create the mapping
            ProgramCourse::firstOrCreate(
                [
                    'program_id' => $program->id,
                    'course_id' => $courses[$courseCode],
                    'program_major_id' => $majorId,
                ],
                [
                    'cognate_field_id' => $cognateFieldId,
                    'applies_to_all_majors' => $majorId === null,
                    'course_type' => $courseType ? $courseType->value : null,
                    'semester_offered' => $this->cleanNull($row['Semester Offered'] ?? ''),
                    'units' => is_numeric(trim($row['Units'] ?? '')) ? (int) trim($row['Units']) : null,
                    'description' => $this->cleanNull($row['Description'] ?? ''),
                    'prerequisite_text' => $this->cleanNull($row['Prerequisite'] ?? ''),
                    'notes' => $this->cleanNull($row['Notes'] ?? ''),
                ]
            );

            $mappings++;
        }

        $this->command->info("  Created {$mappings} program-course mappings.");
    }

    private function cleanNull(?string $value): ?string
    {
        if (! $value) return null;
        $value = trim($value);
        if ($value === '' || $value === 'NULL' || $value === 'None') return null;
        return $value;
    }
}
