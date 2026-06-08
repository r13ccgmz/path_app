<?php

namespace App\Exports;

use App\Models\Enrollee;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EnrollmentProgramExport implements FromQuery, WithHeadings, WithMapping
{
    protected array $programs;
    protected array $termIds;

    public function __construct(array $programs, array $termIds = [])
    {
        $this->programs = $programs;
        $this->termIds = $termIds;
    }

    public function query()
    {
        $query = Enrollee::query()->with(['program', 'programMajor']);

        if (!empty($this->programs)) {
            $query->where(function ($q) {
                $dbPrograms = \App\Models\Program::pluck('name')->toArray();

                foreach ($this->programs as $programName) {
                    if (empty($programName)) {
                        $q->orWhereNull('degree_program')
                          ->orWhere(fn($sub) => $sub->whereNull('program_id')->whereNull('program_major_id'));
                        continue;
                    }

                    $q->orWhere('degree_program', $programName);

                    // Try to split program name and major name based on known programs
                    $matchedProgName = null;
                    $matchedMajorName = null;

                    foreach ($dbPrograms as $dbProg) {
                        if (str_starts_with($programName, $dbProg)) {
                            $remainder = substr($programName, strlen($dbProg));
                            if (str_starts_with($remainder, ' in ')) {
                                $matchedProgName = $dbProg;
                                $matchedMajorName = substr($remainder, 4); // Strip ' in '
                                break;
                            }
                        }
                    }

                    if ($matchedProgName && $matchedMajorName) {
                        $q->orWhere(function ($sub) use ($matchedProgName, $matchedMajorName) {
                            $sub->whereHas('program', fn($p) => $p->where('name', $matchedProgName))
                                ->whereHas('programMajor', fn($pm) => $pm->where('name', $matchedMajorName));
                        });
                    } else {
                        $q->orWhere(function ($sub) use ($programName) {
                            $sub->whereHas('program', fn($p) => $p->where('name', $programName))
                                ->whereNull('program_major_id');
                        });
                    }
                }
            });
        }

        if (!empty($this->termIds)) {
            $query->whereIn('term_id', $this->termIds);
        }

        return $query->orderBy('term_id', 'desc')->orderBy('degree_program')->orderBy('last_name')->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'Term', 'Student Number', 'Last Name', 'First Name', 'Middle Name',
            'Degree/Program', 'Courses Enrolled', 'Total Units',
            'Sex', 'Marital Status', 'Birthdate', 'Nationality', 'Email',
        ];
    }

    public function map($enrollee): array
    {
        return [
            $enrollee->term_id, $enrollee->student_number,
            $enrollee->last_name, $enrollee->first_name, $enrollee->middle_name,
            $enrollee->degree_program, $enrollee->courses_enrolled, $enrollee->total_units,
            $enrollee->sex, $enrollee->marital_status, $enrollee->birthdate?->format('Y-m-d'),
            $enrollee->nationality, $enrollee->email,
        ];
    }
}
