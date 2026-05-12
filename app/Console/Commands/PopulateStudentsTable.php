<?php

namespace App\Console\Commands;

use App\Models\Enrollee;
use App\Models\Graduate;
use App\Models\NormalizationRule;
use App\Models\Program;
use App\Models\ProgramMajor;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateStudentsTable extends Command
{
    protected $signature = 'students:populate
        {--fresh : Delete all existing student records before populating}';

    protected $description = 'Populate the students table from enrollees and graduates data';

    private int $created = 0;
    private int $updated = 0;
    private int $programMatched = 0;
    private int $majorMatched = 0;
    private int $graduateMatched = 0;

    public function handle(): int
    {
        $this->info('Populating students table from enrollees data...');

        if ($this->option('fresh')) {
            if ($this->confirm('This will DELETE all existing student records. Continue?')) {
                Student::query()->forceDelete();
                $this->warn('All student records deleted.');
            } else {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        // Get all distinct student numbers
        $studentNumbers = Enrollee::query()
            ->whereNotNull('student_number')
            ->where('student_number', '!=', '')
            ->distinct()
            ->pluck('student_number');

        $this->info("Found {$studentNumbers->count()} unique students in enrollees.");

        // Preload lookup data
        $normalizationMap = NormalizationRule::getMap('program');
        $programs = Program::all();
        $programMajors = ProgramMajor::with('program')->get();
        $semesterLookup = DB::table('semesters')->pluck('id', 'term_code')->toArray();

        // Preload graduate data (only matched graduates with student numbers)
        $graduates = Graduate::query()
            ->whereNotNull('student_number')
            ->where('student_number', '!=', '')
            ->get()
            ->keyBy('student_number');

        $bar = $this->output->createProgressBar($studentNumbers->count());
        $bar->start();

        foreach ($studentNumbers as $studentNumber) {
            $this->processStudent(
                $studentNumber,
                $normalizationMap,
                $programs,
                $programMajors,
                $semesterLookup,
                $graduates
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Results:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Students created', $this->created],
                ['Students updated', $this->updated],
                ['Programs matched', $this->programMatched],
                ['Majors matched', $this->majorMatched],
                ['Graduates matched', $this->graduateMatched],
                ['Total students', Student::count()],
            ]
        );

        return self::SUCCESS;
    }

    private function processStudent(
        string $studentNumber,
        array $normalizationMap,
        $programs,
        $programMajors,
        array $semesterLookup,
        $graduates,
    ): void {
        // Get the latest enrollment record for this student
        $latest = Enrollee::where('student_number', $studentNumber)
            ->orderByDesc('term_id')
            ->first();

        if (!$latest) {
            return;
        }

        // Get earliest term for admission semester
        $earliestTermId = Enrollee::where('student_number', $studentNumber)
            ->min('term_id');

        $admissionSemesterId = $semesterLookup[$earliestTermId] ?? null;
        $admissionDate = null;
        if ($admissionSemesterId) {
            $admissionDate = DB::table('semesters')
                ->where('id', $admissionSemesterId)
                ->value('start_date');
        }

        // Sum total units across all enrollment terms
        $totalUnits = (int) Enrollee::where('student_number', $studentNumber)
            ->sum('total_units');

        // Attempt program matching
        $programId = $this->matchProgram($latest->degree_program, $normalizationMap, $programs);

        // Attempt program_major matching from graduates data
        $programMajorId = null;
        $graduate = $graduates->get($studentNumber);

        if ($graduate && $programId && $graduate->major_field_raw) {
            $programMajorId = $this->matchMajor($graduate->major_field_raw, $programId, $programMajors);
        }

        // Determine student status
        $status = 'active';
        $graduationDate = null;
        if ($graduate) {
            $status = 'graduated';
            $this->graduateMatched++;
            // We leave graduation_date null — parsing semester text is too fragile
        }

        // Normalize sex value
        $sex = $this->normalizeSex($latest->sex);

        // Normalize marital status
        $maritalStatus = $this->normalizeMaritalStatus($latest->marital_status);

        // Build full name
        $fullName = Student::buildFullName(
            $latest->last_name,
            $latest->first_name,
            $latest->middle_name
        );

        // Upsert
        $existing = Student::withTrashed()->where('student_number', $studentNumber)->first();

        $data = [
            'surname' => $latest->last_name,
            'given_name' => $latest->first_name,
            'middle_name' => $latest->middle_name,
            'full_name' => $fullName,
            'email' => $latest->email ?? "{$studentNumber}@up.edu.ph",
            'birthdate' => $latest->birthdate,
            'sex' => $sex,
            'marital_status' => $maritalStatus,
            'nationality' => $latest->nationality ?: 'Filipino',
            'country_of_origin' => $graduate?->country_of_origin,
            'program_id' => $programId,
            'program_major_id' => $programMajorId,
            'admission_semester_id' => $admissionSemesterId,
            'admission_date' => $admissionDate,
            'student_status' => $status,
            'total_units_earned' => $totalUnits,
        ];

        if ($existing) {
            $existing->update($data);
            if ($existing->trashed()) {
                $existing->restore();
            }
            $this->updated++;
        } else {
            Student::create(array_merge(['student_number' => $studentNumber], $data));
            $this->created++;
        }

        if ($programId) {
            $this->programMatched++;
        }
        if ($programMajorId) {
            $this->majorMatched++;
        }
    }

    /**
     * Match a degree program string to a programs.id.
     *
     * Strategy order:
     * 1. Apply normalization rules
     * 2. Exact name match (with & / and normalization)
     * 3. Program code match
     * 4. Keyword-pattern matching (handles MPAf specializations, PhD-DVST-R, etc.)
     * 5. Fuzzy partial match with & / and normalization
     */
    private function matchProgram(?string $degreeProgram, array $normalizationMap, $programs): ?int
    {
        if (!$degreeProgram) {
            return null;
        }

        // Apply normalization rules first
        $normalized = $degreeProgram;
        foreach ($normalizationMap as $from => $to) {
            if (strcasecmp($normalized, $from) === 0) {
                $normalized = $to;
                break;
            }
        }

        // Normalize & / and for comparison
        $normalizedComparable = $this->normalizeAmpersand($normalized);

        // 1. Try exact match on program name (with & / and normalization)
        $match = $programs->first(fn ($p) =>
            strcasecmp($this->normalizeAmpersand($p->name), $normalizedComparable) === 0
        );
        if ($match) {
            return $match->id;
        }

        // 2. Try matching by program code
        $match = $programs->first(fn ($p) => strcasecmp($p->code, $normalized) === 0);
        if ($match) {
            return $match->id;
        }

        // 3. Keyword-pattern matching for known program families
        $lower = strtolower($normalizedComparable);

        // PhD by Research in Development Studies → PhD-DVST-R
        if (str_contains($lower, 'research') && str_contains($lower, 'development studies')) {
            $match = $programs->first(fn ($p) => $p->code === 'PhD-DVST-R');
            if ($match) return $match->id;
        }

        // PhD in Development Studies → PhD-DVST (must not contain "research")
        if (str_contains($lower, 'doctor') && str_contains($lower, 'development studies') && !str_contains($lower, 'research')) {
            $match = $programs->first(fn ($p) => $p->code === 'PhD-DVST');
            if ($match) return $match->id;
        }

        // PhD in Community Development → PhD-CD
        if (str_contains($lower, 'doctor') && str_contains($lower, 'community development')) {
            $match = $programs->first(fn ($p) => $p->code === 'PhD-CD');
            if ($match) return $match->id;
        }

        // PhD in Extension Education → PhD-EE
        if (str_contains($lower, 'doctor') && str_contains($lower, 'extension education')) {
            $match = $programs->first(fn ($p) => $p->code === 'PhD-EE');
            if ($match) return $match->id;
        }

        // Master of Science in Development Management and Governance → MSDMG
        if (str_contains($lower, 'master of science') && str_contains($lower, 'development management') && str_contains($lower, 'governance')) {
            $match = $programs->first(fn ($p) => $p->code === 'MSDMG');
            if ($match) return $match->id;
        }

        // Master in Development Management and Governance → MDMG (not MS)
        if (str_contains($lower, 'master') && !str_contains($lower, 'master of science') && str_contains($lower, 'development management') && str_contains($lower, 'governance')) {
            $match = $programs->first(fn ($p) => $p->code === 'MDMG');
            if ($match) return $match->id;
        }

        // Master in Public Affairs (any specialization) → MPAf
        if (str_contains($lower, 'master') && str_contains($lower, 'public affairs')) {
            $match = $programs->first(fn ($p) => $p->code === 'MPAf');
            if ($match) return $match->id;
        }

        // MS in Extension Education → MS-EE
        if (str_contains($lower, 'master of science') && str_contains($lower, 'extension education')) {
            $match = $programs->first(fn ($p) => $p->code === 'MS-EE');
            if ($match) return $match->id;
        }

        // MS in Community Development → MS-CD
        if (str_contains($lower, 'master of science') && str_contains($lower, 'community development')) {
            $match = $programs->first(fn ($p) => $p->code === 'MS-CD');
            if ($match) return $match->id;
        }

        // 4. Fuzzy partial match with & / and normalization
        $match = $programs->first(function ($p) use ($normalizedComparable) {
            $programName = strtolower($this->normalizeAmpersand($p->name));
            $input = strtolower($normalizedComparable);
            return str_contains($programName, $input) || str_contains($input, $programName);
        });

        return $match?->id;
    }

    /**
     * Normalize ampersand symbols: replace & with 'and' for consistent comparison.
     */
    private function normalizeAmpersand(string $value): string
    {
        // Replace & (optionally surrounded by spaces) with ' and '
        return preg_replace('/\s*&\s*/', ' and ', $value);
    }

    /**
     * Match a major_field_raw string from graduates to program_majors.id.
     */
    private function matchMajor(?string $majorField, int $programId, $programMajors): ?int
    {
        if (!$majorField) {
            return null;
        }

        // Filter to only majors for this program
        $candidates = $programMajors->where('program_id', $programId);

        // Try exact match
        $match = $candidates->first(fn ($m) => strcasecmp($m->name, $majorField) === 0);
        if ($match) {
            return $match->id;
        }

        // Try partial match (contains)
        $match = $candidates->first(function ($m) use ($majorField) {
            return str_contains(strtolower($m->name), strtolower($majorField))
                || str_contains(strtolower($majorField), strtolower($m->name));
        });

        return $match?->id;
    }

    /**
     * Normalize sex values from enrollee data to match the migration enum.
     */
    private function normalizeSex(?string $sex): ?string
    {
        if (!$sex) {
            return null;
        }

        $lower = strtolower(trim($sex));

        return match (true) {
            in_array($lower, ['m', 'male']) => 'male',
            in_array($lower, ['f', 'female']) => 'female',
            default => 'other',
        };
    }

    /**
     * Normalize marital status values to match the migration enum.
     */
    private function normalizeMaritalStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        $lower = strtolower(trim($status));

        return match (true) {
            in_array($lower, ['s', 'single']) => 'single',
            in_array($lower, ['m', 'married']) => 'married',
            in_array($lower, ['w', 'widowed']) => 'widowed',
            str_starts_with($lower, 'sep') => 'separated',
            str_starts_with($lower, 'div') => 'divorced',
            default => null,
        };
    }
}
