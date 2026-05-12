<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

class StudentAcademicProgressSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected array $academicProgress;

    public function __construct(array $academicProgress)
    {
        $this->academicProgress = $academicProgress;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->academicProgress as $progress) {
            $program = $progress['program'] ?? null;
            $programName = is_object($program) ? $program->name : ($progress['program_name'] ?? '-');
            $totalEarned = $progress['total_earned'] ?? 0;
            $totalRequired = $progress['total_required'] ?? 0;
            $completionPct = $totalRequired > 0 ? round(($totalEarned / $totalRequired) * 100, 1) : 0;

            $rows[] = [
                $programName,
                $totalEarned,
                $totalRequired,
                $completionPct . '%',
                $progress['gwa'] ? number_format($progress['gwa'], 4) : '-',
                $progress['completed_courses'] ?? 0,
            ];

            // Add type breakdown rows (indented with prefix)
            if (!empty($progress['type_progress'])) {
                foreach ($progress['type_progress'] as $tp) {
                    $tpPct = ($tp['required_units'] ?? 0) > 0
                        ? round(($tp['earned_units'] / $tp['required_units']) * 100, 1) : 0;
                    $rows[] = [
                        '  → ' . $tp['label'],
                        $tp['earned_units'],
                        $tp['required_units'] ?? '-',
                        $tpPct . '%',
                        '',
                        $tp['completed_courses'],
                    ];
                }
            }

            // Empty row separator
            $rows[] = ['', '', '', '', '', ''];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Program / Type',
            'Units Earned',
            'Units Required',
            'Completion %',
            'GWA',
            'Completed Courses',
        ];
    }

    public function title(): string
    {
        return 'Academic Progress';
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
