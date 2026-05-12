<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\StudentProfileSheet;
use App\Exports\Sheets\StudentEnrollmentsSheet;
use App\Exports\Sheets\StudentMilestonesSheet;
use App\Exports\Sheets\StudentAcademicOutputsSheet;
use App\Exports\Sheets\StudentGradesSheet;

class StudentHistoryExport implements WithMultipleSheets
{
    use Exportable;

    protected string $studentNumber;
    protected array $studentInfo;
    protected array $academicProgress;
    protected array $milestones;
    protected array $academicOutputs;

    public function __construct(string $studentNumber, array $studentInfo, array $academicProgress, array $milestones, array $academicOutputs)
    {
        $this->studentNumber = $studentNumber;
        $this->studentInfo = $studentInfo;
        $this->academicProgress = $academicProgress;
        $this->milestones = $milestones;
        $this->academicOutputs = $academicOutputs;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new StudentProfileSheet($this->studentInfo);
        $sheets[] = new StudentEnrollmentsSheet($this->studentNumber);
        $sheets[] = new StudentGradesSheet($this->academicProgress, $this->studentNumber, $this->studentInfo['name'] ?? 'Unknown');
        $sheets[] = new StudentMilestonesSheet($this->milestones, $this->studentNumber, $this->studentInfo['name'] ?? 'Unknown');
        $sheets[] = new StudentAcademicOutputsSheet($this->academicOutputs, $this->studentInfo['name'] ?? 'Unknown');

        return $sheets;
    }
}
