<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class StudentProfileSheet extends StringValueBinder implements FromArray, WithTitle, WithStyles, ShouldAutoSize, WithCustomValueBinder, WithColumnFormatting
{
    protected array $studentInfo;
    protected array $sectionRows = [];

    public function __construct(array $studentInfo)
    {
        $this->studentInfo = $studentInfo;
    }

    public function array(): array
    {
        $rows = [];
        $rowNum = 1;

        // ── STUDENT DEMOGRAPHICS ──
        $rows[] = ['STUDENT DEMOGRAPHICS', ''];
        $this->sectionRows[] = $rowNum; $rowNum++;

        $rows[] = ['Name', $this->studentInfo['name'] ?? '-'];  $rowNum++;
        $rows[] = ['Student Number', $this->studentInfo['student_number'] ?? '-']; $rowNum++;
        $rows[] = ['Student Status', empty($this->studentInfo['student_status']) ? '-' : ucwords(str_replace('-', ' ', $this->studentInfo['student_status']))]; $rowNum++;
        $rows[] = ['Sex', ucfirst($this->studentInfo['sex'] ?? '-')]; $rowNum++;
        $rows[] = ['Birthdate', $this->studentInfo['birthdate'] ?? '-']; $rowNum++;
        $rows[] = ['Nationality', (!empty($this->studentInfo['nationality']) && is_array($this->studentInfo['nationality'])) ? implode(', ', $this->studentInfo['nationality']) : ($this->studentInfo['nationality'] ?? '-')]; $rowNum++;
        $rows[] = ['Marital Status', ucfirst($this->studentInfo['marital_status'] ?? '-')]; $rowNum++;
        $rows[] = ['Country of Origin', $this->studentInfo['country_of_origin'] ?? '-']; $rowNum++;
        $rows[] = ['Address', (!empty($this->studentInfo['address']) && is_array($this->studentInfo['address'])) ? implode(', ', $this->studentInfo['address']) : ($this->studentInfo['address'] ?? '-')]; $rowNum++;

        // ── CONTACT INFORMATION ──
        $rows[] = ['', '']; $rowNum++;
        $rows[] = ['CONTACT INFORMATION', ''];
        $this->sectionRows[] = $rowNum; $rowNum++;

        // Phone numbers are kept as text via columnFormats()
        $primaryContact = $this->studentInfo['primary_contact_number'] ?? '-';
        $rows[] = ['Primary Contact Number', $primaryContact]; $rowNum++;

        $altContacts = '-';
        if (!empty($this->studentInfo['alternative_contact_number']) && is_array($this->studentInfo['alternative_contact_number'])) {
            $altContacts = implode(', ', $this->studentInfo['alternative_contact_number']);
        }
        $rows[] = ['Alternative Contact Number(s)', $altContacts]; $rowNum++;

        $rows[] = ['Primary Email', $this->studentInfo['email'] ?? '-']; $rowNum++;
        $rows[] = ['UP Email', $this->studentInfo['up_email'] ?? '-']; $rowNum++;
        $rows[] = ['Alternative Email(s)', (!empty($this->studentInfo['alternative_email']) && is_array($this->studentInfo['alternative_email'])) ? implode(', ', $this->studentInfo['alternative_email']) : '-']; $rowNum++;

        // ── SOCIAL MEDIA & AFFILIATION ──
        if (($this->studentInfo['social_facebook'] ?? null) || ($this->studentInfo['social_linkedin'] ?? null) || ($this->studentInfo['social_other'] ?? null) || ($this->studentInfo['institution_affiliated'] ?? null)) {
            $rows[] = ['', '']; $rowNum++;
            $rows[] = ['SOCIAL MEDIA & AFFILIATION', ''];
            $this->sectionRows[] = $rowNum; $rowNum++;

            if (!empty($this->studentInfo['social_facebook'])) { $rows[] = ['Facebook', $this->studentInfo['social_facebook']]; $rowNum++; }
            if (!empty($this->studentInfo['social_linkedin'])) { $rows[] = ['LinkedIn', $this->studentInfo['social_linkedin']]; $rowNum++; }
            if (!empty($this->studentInfo['institution_affiliated'])) { $rows[] = ['Office / School / Institution Affiliated', is_array($this->studentInfo['institution_affiliated']) ? implode(', ', $this->studentInfo['institution_affiliated']) : $this->studentInfo['institution_affiliated']]; $rowNum++; }
            if (!empty($this->studentInfo['social_other'])) { $rows[] = ['Other Social Media / Contact Handles', is_array($this->studentInfo['social_other']) ? implode(', ', $this->studentInfo['social_other']) : $this->studentInfo['social_other']]; $rowNum++; }
        }

        // ── ADMISSION INFORMATION ──
        $rows[] = ['', '']; $rowNum++;
        $rows[] = ['ADMISSION INFORMATION', ''];
        $this->sectionRows[] = $rowNum; $rowNum++;

        $rows[] = ['Applicant Status', empty($this->studentInfo['applicant_status']) ? '-' : ucwords(str_replace('-', ' ', $this->studentInfo['applicant_status']))]; $rowNum++;
        $rows[] = ['Admission Semester', $this->studentInfo['admission_semester'] ?? '-']; $rowNum++;
        $rows[] = ['Admission Date', $this->studentInfo['admission_date'] ?? '-']; $rowNum++;

        if (!empty($this->studentInfo['registration_adviser_name'])) {
            $regAdviserDesignation = !empty($this->studentInfo['registration_adviser_designation']) ? ' — ' . $this->studentInfo['registration_adviser_designation'] : '';
            $rows[] = ['Registration Adviser', $this->studentInfo['registration_adviser_name'] . $regAdviserDesignation . (!empty($this->studentInfo['registration_adviser_appointed_date']) ? " (Appointed: {$this->studentInfo['registration_adviser_appointed_date']})" : '')]; $rowNum++;
        }

        if (!empty($this->studentInfo['committee_members']) || !empty($this->studentInfo['adviser_name'])) {
            $rows[] = ['', '']; $rowNum++;
            $rows[] = ['ADMISSION ADVISORY COMMITTEE', ''];
            $this->sectionRows[] = $rowNum; $rowNum++;
            
            if (!empty($this->studentInfo['committee_members'])) {
                foreach ($this->studentInfo['committee_members'] as $cm) {
                    $nameWithDesignation = $cm['name'] . (!empty($cm['designation']) ? " ({$cm['designation']})" : '');
                    $appointed = !empty($cm['appointed_date']) ? " - (Appointed: {$cm['appointed_date']})" : '';
                    
                    $rows[] = [$cm['role'] ?? 'Member', $nameWithDesignation . $appointed]; $rowNum++;
                    
                    if (!empty($cm['term_start']) || !empty($cm['term_end'])) {
                        $termStr = 'Term: ' . ($cm['term_start'] ?? '-') . ' to ' . ($cm['term_end'] ?? 'Present');
                        $rows[] = ['', $termStr]; $rowNum++;
                    }
                }
            }
        }

        // ── GRADUATION INFORMATION ──
        if (!empty($this->studentInfo['graduation_records'])) {
            foreach ($this->studentInfo['graduation_records'] as $idx => $grad) {
                $rows[] = ['', '']; $rowNum++;
                $label = count($this->studentInfo['graduation_records']) > 1
                    ? 'GRADUATION INFORMATION — ' . ($grad['program_name'] ?? $grad['degree'] ?? '#' . ($idx + 1))
                    : 'GRADUATION INFORMATION';
                $rows[] = [$label, ''];
                $this->sectionRows[] = $rowNum; $rowNum++;

                $rows[] = ['Semester Graduated', $grad['semester_graduated'] ?? '-']; $rowNum++;
                $rows[] = ['Degree', $grad['degree'] ?? '-']; $rowNum++;
                $rows[] = ['Program', $grad['program_name'] ?? '-']; $rowNum++;
                $rows[] = ['Major Field', $grad['major'] ?? '-']; $rowNum++;

                if (!empty($grad['committee_data'])) {
                    $rows[] = ['', '']; $rowNum++;
                    $gradLabel = count($this->studentInfo['graduation_records']) > 1
                        ? 'GRADUATION ADVISORY COMMITTEE ' . ($grad['program_name'] ?? $grad['degree'] ?? '')
                        : 'GRADUATION ADVISORY COMMITTEE';
                    $rows[] = [$gradLabel, ''];
                    $this->sectionRows[] = $rowNum; $rowNum++;
                    foreach ($grad['committee_data'] as $cm) {
                        $appointedDate = $cm['formatted_appointed_date'] ?? $cm['appointed_date'] ?? null;
                        $appointed = !empty($appointedDate) ? " - (Appointed: {$appointedDate})" : '';
                        $nameWithDesig = $cm['name'] . (!empty($cm['designation']) ? " ({$cm['designation']})" : '');
                        
                        $rows[] = [$cm['role_label'] ?? $cm['role'] ?? 'Member', $nameWithDesig . $appointed]; $rowNum++;
                        
                        if (!empty($cm['term_start']) || !empty($cm['term_end'])) {
                            $termStr = 'Term: ' . ($cm['term_start'] ?? '-') . ' to ' . ($cm['term_end'] ?? 'Present');
                            $rows[] = ['', $termStr]; $rowNum++;
                        }
                    }
                }
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Profile';
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Build array first to know section rows
        if (empty($this->sectionRows)) {
            $this->array();
        }

        $styles = [
            'A' => ['font' => ['bold' => true]],
        ];

        foreach ($this->sectionRows as $row) {
            $styles[$row] = [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE8F5E9'],
                ],
            ];
        }

        return $styles;
    }
}
