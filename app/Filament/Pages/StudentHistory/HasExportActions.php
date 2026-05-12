<?php

namespace App\Filament\Pages\StudentHistory;

use App\Exports\StudentHistoryExport;
use App\Exports\StudentSummaryExport;
use App\Models\Enrollee;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Handles PDF and Excel download/export actions for the StudentHistory page.
 */
trait HasExportActions
{
    public function downloadPdf()
    {
        if (empty($this->studentNumber)) {
            return;
        }

        $studentInfo = $this->studentInfo;
        $records = Enrollee::where('student_number', $this->studentNumber)->orderBy('term_id')->get();
        $academicProgress = $this->getAllAcademicProgress();
        $milestones = $this->getStudentMilestones() ?? [];
        $academicOutputs = $this->getAcademicOutputs() ?? [];

        $pdf = Pdf::loadView('pdf.student-history-pdf', compact('studentInfo', 'records', 'academicProgress', 'milestones', 'academicOutputs'));

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'student_history_' . $this->studentNumber . '.pdf');
    }

    public function downloadXls()
    {
        if (empty($this->studentNumber)) {
            return;
        }

        return Excel::download(
            new StudentHistoryExport(
                $this->studentNumber, 
                $this->studentInfo, 
                $this->getAllAcademicProgress(),
                $this->getStudentMilestones() ?? [],
                $this->getAcademicOutputs() ?? []
            ),
            'student_history_' . $this->studentNumber . '.xlsx'
        );
    }

    public function downloadDetailedPdf()
    {
        if (empty($this->studentNumber)) {
            return;
        }

        $studentInfo = $this->studentInfo;
        $records = Enrollee::where('student_number', $this->studentNumber)->orderBy('term_id')->get();
        $academicProgress = $this->getAllAcademicProgress();
        $milestones = $this->getStudentMilestones() ?? [];
        $academicOutputs = $this->getAcademicOutputs() ?? [];

        $pdf = Pdf::loadView('pdf.student-history-detailed-pdf', compact('studentInfo', 'records', 'academicProgress', 'milestones', 'academicOutputs'));

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'student_history_with_grades_' . $this->studentNumber . '.pdf');
    }

    public function downloadSummaryXls()
    {
        if (empty($this->studentNumber)) {
            return;
        }

        return Excel::download(
            new StudentSummaryExport(
                $this->studentNumber,
                $this->studentInfo
            ),
            'student_summary_' . $this->studentNumber . '.xlsx'
        );
    }
}
