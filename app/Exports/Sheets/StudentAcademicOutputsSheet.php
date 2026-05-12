<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class StudentAcademicOutputsSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    protected array $outputs;
    protected string $studentName;

    public function __construct(array $outputs, string $studentName = 'Unknown')
    {
        $this->outputs = $outputs;
        $this->studentName = $studentName;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->outputs as $ao) {
            $committee = '';
            if (!empty($ao['committee'])) {
                $committeeParts = [];
                foreach ($ao['committee'] as $cm) {
                    $line = $cm['name'] . " (" . ($cm['role_label'] ?? $cm['role']) . ")";
                    if (!empty($cm['term_start']) || !empty($cm['term_end'])) {
                        $line .= "\n  Term: " . ($cm['term_start'] ?? '-') . ' to ' . ($cm['term_end'] ?? 'Present');
                    }
                    $committeeParts[] = $line;
                }
                $committee = implode("\n", $committeeParts);
            }

            $primaryAuthors = !empty($ao['primary_authors']) ? implode('; ', $ao['primary_authors']) : '-';
            $coAuthors = !empty($ao['co_authors']) ? implode('; ', $ao['co_authors']) : '-';

            $rows[] = [
                $ao['title'] ?? '-',
                $primaryAuthors,
                $coAuthors,
                $ao['type_label'] ?? ucfirst(str_replace('-', ' ', $ao['type'] ?? '-')),
                ucfirst(str_replace('-', ' ', $ao['status'] ?? '-')),
                !empty($ao['term_code']) ? '[' . $ao['term_code'] . ']' . (!empty($ao['semester_label']) ? ' ' . $ao['semester_label'] : '') : '-',
                $ao['proposal_defense_date'] ?? '-',
                ucfirst(str_replace('-', ' ', $ao['proposal_defense_result'] ?? '-')),
                $ao['final_defense_date'] ?? '-',
                ucfirst(str_replace('-', ' ', $ao['final_defense_result'] ?? '-')),
                $ao['date_submitted'] ?? '-',
                $ao['drive_link'] ?? '-',
                $committee,
                $ao['abstract'] ?? '-',
                !empty($ao['keywords']) ? (is_array($ao['keywords']) ? implode(', ', $ao['keywords']) : $ao['keywords']) : '-'
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Title',
            'Primary Author(s)',
            'Co-Author(s)',
            'Type',
            'Status',
            'Semester / Term',
            'Proposal Defense Date',
            'Proposal Defense Result',
            'Final Defense Date',
            'Final Defense Result',
            'Date Submitted',
            'Drive Link',
            'Advisory Committee',
            'Abstract',
            'Keywords'
        ];
    }

    public function title(): string
    {
        return 'Academic Outputs';
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Disable auto-size for column M so our fixed width is respected
                $sheet->getColumnDimension('M')->setAutoSize(false);
                $sheet->getColumnDimension('M')->setWidth(50);

                // Apply wrap text + vertical top to all M cells
                $sheet->getStyle('M1:M' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('M1:M' . $highestRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

                // Calculate height based on newlines for wrapped text to ensure it expands at first view
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('M'.$row)->getValue();
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
