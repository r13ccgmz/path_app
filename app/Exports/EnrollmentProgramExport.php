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
        $query = Enrollee::query();

        if (!empty($this->programs)) {
            $query->whereIn('degree_program', $this->programs);
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
