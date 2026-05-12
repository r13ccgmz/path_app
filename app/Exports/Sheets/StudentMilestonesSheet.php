<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

class StudentMilestonesSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles
{
    protected array $milestones;
    protected string $studentNumber;
    protected string $studentName;

    public function __construct(array $milestones, string $studentNumber = '-', string $studentName = 'Unknown')
    {
        $this->milestones = $milestones;
        $this->studentNumber = $studentNumber;
        $this->studentName = $studentName;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->milestones as $m) {
            $rows[] = [
                $this->studentNumber,
                $this->studentName,
                $m['name'] ?? '-',
                $m['status_label'] ?? ucfirst(str_replace('-', ' ', $m['status'] ?? '-')),
                $m['date_completed'] ?? '-',
                $m['remarks'] ?? '-'
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Student Number',
            'Name',
            'Milestone',
            'Status',
            'Date Completed',
            'Remarks'
        ];
    }

    public function title(): string
    {
        return 'Academic Milestones';
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
