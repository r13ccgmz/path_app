<?php

namespace App\Exports;

use App\Models\Enrollee;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EnrolleeExport implements FromQuery, WithHeadings, WithMapping
{
    protected string|array|null $termIds;

    public function __construct(string|array|null $termIds = null)
    {
        $this->termIds = $termIds;
    }

    public function query()
    {
        $query = Enrollee::query();
        if (!empty($this->termIds)) {
            $ids = is_array($this->termIds) ? $this->termIds : [$this->termIds];
            $query->whereIn('term_id', $ids);
        }
        return $query->orderBy('last_name')->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'Term', 'ID', 'Student Number', 'Last Name', 'First Name',
            'Middle Name', 'Degree/Program', 'Courses Enrolled', 'Total Units',
            'Sex', 'Marital Status', 'Birthdate', 'Nationality', 'Email',
        ];
    }

    public function map($enrollee): array
    {
        return [
            $enrollee->term_id, $enrollee->campus_id, $enrollee->student_number,
            $enrollee->last_name, $enrollee->first_name, $enrollee->middle_name,
            $enrollee->degree_program, $enrollee->courses_enrolled, $enrollee->total_units,
            $enrollee->sex, $enrollee->marital_status, $enrollee->birthdate?->format('Y-m-d'),
            $enrollee->nationality, $enrollee->email,
        ];
    }
}
