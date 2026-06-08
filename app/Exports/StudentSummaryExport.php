<?php

namespace App\Exports;

use App\Models\Enrollee;
use App\Models\SystemSetting;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class StudentSummaryExport extends StringValueBinder implements FromArray, WithTitle, ShouldAutoSize, WithStyles, WithCustomValueBinder, WithEvents
{
    use Exportable;

    protected string $studentNumber;
    protected array $studentInfo;
    protected array $titleRows = [];
    protected array $headerRows = [];

    public function __construct(string $studentNumber, array $studentInfo)
    {
        $this->studentNumber = $studentNumber;
        $this->studentInfo = $studentInfo;
    }

    public function array(): array
    {
        $rows = [];
        $ftThreshold = 9;
        try { $ftThreshold = (int) (SystemSetting::get('full_time_units_threshold', 9)); } catch (\Exception $e) {}

        $student = \App\Models\Student::with(['program', 'adviser', 'admissionSemester'])->where('student_number', $this->studentNumber)->first();
        $graduates = \App\Models\Graduate::with('term.academicYear')->where('student_number', $this->studentNumber)->orderBy('id', 'asc')->get();
        $records = Enrollee::with(['term.academicYear', 'program', 'programMajor'])->where('student_number', $this->studentNumber)->orderBy('term_id')->get();

        $currentRow = 1;

        // SECTION: Profile
        $rows[] = ['STUDENT PROFILE'];
        $this->titleRows[] = $currentRow;
        $currentRow++;

        $rows[] = [
            'Student Number', 'Name', 'Current Student Status', 'Sex', 'Birthdate',
            'Nationality', 'Country of Origin', 'Primary Email', 'UP Email',
            'Primary Contact', 'Admission Semester', 'Applicant Status'
        ];
        $this->headerRows[] = $currentRow;
        $currentRow++;

        $rows[] = [
            $this->studentNumber,
            $this->studentInfo['name'] ?? '-',
            empty($this->studentInfo['student_status']) ? '-' : ucwords(str_replace('-', ' ', $this->studentInfo['student_status'])),
            ucfirst($this->studentInfo['sex'] ?? '-'),
            $this->studentInfo['birthdate'] ?? '-',
            (!empty($this->studentInfo['nationality']) && is_array($this->studentInfo['nationality'])) ? implode(', ', $this->studentInfo['nationality']) : ($this->studentInfo['nationality'] ?? '-'),
            $this->studentInfo['country_of_origin'] ?? '-',
            $this->studentInfo['email'] ?? '-',
            $this->studentInfo['up_email'] ?? '-',
            $this->studentInfo['primary_contact_number'] ?? '-',
            $this->studentInfo['admission_semester'] ?? '-',
            !empty($this->studentInfo['applicant_status']) ? ucwords(str_replace('-', ' ', $this->studentInfo['applicant_status'])) : '-'
        ];
        $currentRow++;

        $rows[] = ['']; // Blank row
        $currentRow++;

        // SECTION: Graduation Records
        if ($graduates->isNotEmpty()) {
            $rows[] = ['GRADUATION RECORDS'];
            $this->titleRows[] = $currentRow;
            $currentRow++;

            $rows[] = ['Degree', 'Program', 'Major Field', 'Semester Graduated', 'Graduation Committee'];
            $this->headerRows[] = $currentRow;
            $currentRow++;

            foreach ($graduates as $grad) {
                $semLabel = $grad->term?->label ?? $grad->semester_graduated ?? '-';
                
                // Build committee string
                $committeeParts = [];
                if (!empty($grad->committee_data) && is_array($grad->committee_data)) {
                    foreach ($grad->committee_data as $cm) {
                        $role = $cm['role_label'] ?? $cm['role'] ?? 'Member';
                        $name = $cm['name'] ?? '';
                        if ($name) {
                            $committeeParts[] = "{$name} ({$role})";
                        }
                    }
                } else {
                    if (!empty($grad->chair) && $grad->chair !== 'N/A') $committeeParts[] = "{$grad->chair} (Chair)";
                    if (!empty($grad->co_chair) && $grad->co_chair !== 'N/A') $committeeParts[] = "{$grad->co_chair} (Co-Chair)";
                    for ($i = 1; $i <= 5; $i++) {
                        $prop = "member{$i}";
                        if (!empty($grad->$prop) && $grad->$prop !== 'N/A') {
                            $committeeParts[] = "{$grad->$prop} (Member)";
                        }
                    }
                }
                $committeeStr = empty($committeeParts) ? '-' : implode("\n", $committeeParts);

                $rows[] = [
                    $grad->degree ?? '-',
                    $grad->program_name ?? '-',
                    $grad->major ?? '-',
                    $semLabel,
                    $committeeStr
                ];
                $currentRow++;
            }

            $rows[] = ['']; // Blank row
            $currentRow++;
        }

        // SECTION: Enrollment History
        $rows[] = ['ENROLLMENT HISTORY'];
        $this->titleRows[] = $currentRow;
        $currentRow++;

        $rows[] = ['Student Number', 'Name', 'Term Enrolled', 'Enrollment Program', 'Courses Enrolled', 'Total Units', 'Enrollment Status'];
        $this->headerRows[] = $currentRow;
        $currentRow++;

        foreach ($records as $record) {
            $units = (int) ($record->total_units ?? 0);
            $enrollmentStatus = $record->enrollment_status ?? ($units >= $ftThreshold ? 'Full-Time' : 'Part-Time');

            $rows[] = [
                $this->studentNumber,
                $this->studentInfo['name'] ?? '-',
                $record->term?->label ?? $record->term_id ?? '-',
                $record->program_display,
                $record->courses_enrolled ?? '-',
                strval($units),
                $enrollmentStatus,
            ];
            $currentRow++;
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Student Summary';
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];

        foreach ($this->titleRows as $row) {
            $styles[$row] = [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF1F2937']], // gray-800
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF3F4F6'], // gray-100
                ],
            ];
            $sheet->mergeCells("A{$row}:E{$row}");
        }

        foreach ($this->headerRows as $row) {
            $styles[$row] = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF10B981'], // emerald-500
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
        }

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Disable auto-size for column E so our fixed width is respected
                $sheet->getColumnDimension('E')->setAutoSize(false);
                $sheet->getColumnDimension('E')->setWidth(50);

                // Apply wrap text + vertical top to all E cells
                $sheet->getStyle('E1:E' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('E1:E' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                // Calculate height based on newlines for wrapped text to ensure it expands at first view
                for ($row = 1; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('E'.$row)->getValue();
                    if (is_string($cellValue) && strpos($cellValue, "\n") !== false) {
                        $lines = substr_count($cellValue, "\n") + 1;
                        $sheet->getRowDimension($row)->setRowHeight($lines * 15);
                    } else {
                        $sheet->getRowDimension($row)->setRowHeight(-1);
                    }
                }
            },
        ];
    }
}
