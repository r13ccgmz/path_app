<?php

namespace App\Exports\Sheets;

use App\Models\Enrollee;
use App\Models\Student;
use App\Models\SystemSetting;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

class StudentEnrollmentsSheet implements FromQuery, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected string $studentNumber;
    protected string $studentName;
    protected int $ftThreshold;

    public function __construct(string $studentNumber)
    {
        $this->studentNumber = $studentNumber;
        $student = Student::where('student_number', $studentNumber)->first();
        $this->studentName = $student ? "{$student->surname}, {$student->given_name}" : '-';
        $this->ftThreshold = (int) SystemSetting::get('full_time_units_threshold', 9);
    }

    public function query()
    {
        return Enrollee::with('term.academicYear')->where('student_number', $this->studentNumber)
            ->orderBy('term_id');
    }

    public function headings(): array
    {
        return [
            'Student Number',
            'Name',
            'Term',
            'Degree / Program',
            'Courses Enrolled',
            'Total Units',
            'Enrollment Status',
            'Source'
        ];
    }

    public function map($enrollee): array
    {
        $units = (int) ($enrollee->total_units ?? 0);
        $status = $enrollee->enrollment_status ?? ($units >= $this->ftThreshold ? 'Full-Time' : 'Part-Time');

        return [
            $this->studentNumber,
            $this->studentName,
            $enrollee->term?->label ?? $enrollee->term_id,
            $enrollee->degree_program,
            $enrollee->courses_enrolled,
            strval($units),
            $status,
            ucfirst($enrollee->source)
        ];
    }

    public function title(): string
    {
        return 'Enrollment History';
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF10B981'],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
