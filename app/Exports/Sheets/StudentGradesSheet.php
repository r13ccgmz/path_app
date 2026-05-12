<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

class StudentGradesSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected array $academicProgress;
    protected string $studentNumber;
    protected string $studentName;

    public function __construct(array $academicProgress, string $studentNumber = '-', string $studentName = 'Unknown')
    {
        $this->academicProgress = $academicProgress;
        $this->studentNumber = $studentNumber;
        $this->studentName = $studentName;
    }

    public function array(): array
    {
        $rows = [];
        $allCourses = [];

        foreach ($this->academicProgress as $progress) {
            $program = $progress['program'] ?? null;
            $programName = is_object($program) ? $program->name : ($progress['program_name'] ?? '-');

            if (!empty($progress['courses_taken_by_type'])) {
                foreach ($progress['courses_taken_by_type'] as $typeLabel => $courses) {
                    $flatCourses = isset($courses[0]) ? $courses : collect($courses)->flatten(1)->all();
                    
                    foreach ($flatCourses as $course) {
                        $course['programName'] = $programName;
                        $allCourses[] = $course;
                    }
                }
            }
        }

        // Sort courses by term_taken (chronological)
        usort($allCourses, function ($a, $b) {
            $termA = $a['term_taken'] ?? '';
            $termB = $b['term_taken'] ?? '';
            return strcmp($termA, $termB);
        });

        foreach ($allCourses as $course) {
            $rows[] = [
                $this->studentNumber,
                $this->studentName,
                $course['programName'],
                $course['course_code'] ?? '-',
                $course['course_name'] ?? '-',
                strtoupper($course['type'] ?? '-'),
                $course['units'] ?? '-',
                $course['grade'] ?? '-',
                $course['term_taken'] ?? '-',
                ucfirst(strtolower($course['status'] ?? '-')),
            ];
        }

        if (!empty($this->academicProgress)) {
            $rows[] = ['', '', '', '', '', '', '', '', '', '']; // Spacer
            $rows[] = ['', '', 'ACADEMIC SUMMARY', '', '', '', '', '', '', ''];

            foreach ($this->academicProgress as $progress) {
                $program = $progress['program'] ?? null;
                $programName = is_object($program) ? $program->name : ($progress['program_name'] ?? '-');
                $totalEarned = $progress['total_earned'] ?? 0;
                $totalRequired = $progress['total_required'] ?? 0;
                $completionPct = $totalRequired > 0 ? round(($totalEarned / $totalRequired) * 100, 1) : 0;
                $gwa = $progress['gwa'] ? number_format($progress['gwa'], 4) : '-';

                $rows[] = ['', '', $programName, '', '', '', '', '', '', ''];
                $rows[] = ['', '', '', '', 'Total Units Earned:', '', $totalEarned, '', '', ''];
                $rows[] = ['', '', '', '', 'Total Units Required:', '', $totalRequired ?: '?', '', '', ''];
                $rows[] = ['', '', '', '', 'Completion Percentage:', '', $completionPct . '%', '', '', ''];
                $rows[] = ['', '', '', '', 'General Weighted Average (GWA):', '', '', strval($gwa), '', ''];
                $rows[] = ['', '', '', '', '', '', '', '', '', '']; // Spacer
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Student Number',
            'Name',
            'Program',
            'Course Code',
            'Course Name',
            'Type',
            'Units',
            'Grade',
            'Term Taken',
            'Status'
        ];
    }

    public function title(): string
    {
        return 'Course Grades';
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
