<?php

namespace App\Filament\Pages\StudentHistory;

use App\Models\MilestoneTemplate;
use App\Models\Student;
use App\Models\StudentMilestone;
use Filament\Notifications\Notification;

/**
 * HasMilestoneManagement trait extracted from StudentHistory.
 */
trait HasMilestoneManagement
{

    // ══════════════════════════════════════════════════════════
    // ── Concern 3: Milestone Management ──
    // ══════════════════════════════════════════════════════════

    /**
     * Get milestones for the current student.
     * Auto-generates from templates on first access.
     */
    public function getStudentMilestones(): ?array
    {
        $student = $this->getStudentRecord();
        if (!$student) return null;

        return $student->milestones()
            ->with(['milestoneTemplate', 'semester'])
            ->orderBy('category')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'category' => $m->category,
                'category_label' => $m->category_label,
                'status' => $m->status,
                'status_label' => $m->status_label,
                'status_color' => $m->status_color,
                'date_started' => $m->date_started?->format('F j, Y'),
                'date_completed' => $m->date_completed?->format('F j, Y'),
                'remarks' => $m->remarks,
                'supporting_document' => $m->supporting_document,
                'is_from_template' => $m->milestone_template_id !== null,
            ])
            ->toArray();
    }


    /**
     * Auto-generate student milestones from applicable templates.
     */
    private function generateMilestonesFromTemplates(Student $student): void
    {
        $program = $student->program;
        if (!$program) return;

        $degreeLevel = match ($program->degree_level?->value ?? $program->degree_level) {
            'master', 'master_of_science' => 'masters',
            'doctorate' => 'doctorate',
            default => null,
        };

        $templates = MilestoneTemplate::forProgram($student->program_id, $degreeLevel)
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            StudentMilestone::create([
                'student_id' => $student->id,
                'milestone_template_id' => $template->id,
                'name' => $template->name,
                'category' => $template->category,
                'status' => null,
            ]);
        }
    }


    /**
     * Manually trigger the generation of template milestones.
     */
    public function loadTemplateMilestones(): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        // If the student's primary program_id is missing, try to infer it from their enrollments
        if (!$student->program_id) {
            $latestProgram = $student->studentPrograms()->latest('created_at')->first();
            if ($latestProgram) {
                $student->update(['program_id' => $latestProgram->program_id]);
                // Refresh the model to ensure relations are updated
                $student->refresh();
            }
        }

        if (!$student->program_id) {
            Notification::make()
                ->warning()
                ->title('Program Required')
                ->body('Please assign a program to this student before loading templates.')
                ->send();
            return;
        }

        // Only generate if none exist to prevent duplicates
        if ($student->milestones()->whereNotNull('milestone_template_id')->count() > 0) {
            Notification::make()
                ->warning()
                ->title('Templates Already Loaded')
                ->body('Template milestones have already been loaded for this student.')
                ->send();
            return;
        }

        $this->generateMilestonesFromTemplates($student);

        Notification::make()
            ->success()
            ->title('Templates Loaded')
            ->body('Program milestone templates have been successfully loaded.')
            ->send();
    }


    /**
     * Update milestone status.
     */
    public function updateMilestoneStatus(int $milestoneId, string $status): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        $milestone = StudentMilestone::where('id', $milestoneId)
            ->where('student_id', $student->id)
            ->first();

        if (!$milestone) return;

        $data = ['status' => $status ?: null];

        if ($status === 'completed') {
            $data['date_completed'] = now();
            $data['verified_by'] = auth()->id();
            $data['verified_at'] = now();
        } elseif ($status === 'in-progress' && !$milestone->date_started) {
            $data['date_started'] = now();
        }

        $milestone->update($data);

        Notification::make()
            ->title('Milestone Updated')
            ->body("{$milestone->name} marked as {$status}")
            ->success()
            ->duration(3000)
            ->send();
    }


    /**
     * Update milestone name.
     */
    public function updateMilestoneName(int $milestoneId, string $name): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        $milestone = StudentMilestone::where('id', $milestoneId)
            ->where('student_id', $student->id)
            ->first();

        if (!$milestone || empty(trim($name))) return;

        $milestone->update(['name' => trim($name)]);

        Notification::make()
            ->title('Name saved')
            ->success()
            ->duration(2000)
            ->send();
            
        $this->search();
    }


    /**
     * Update milestone remarks.
     */
    public function updateMilestoneRemarks(int $milestoneId, ?string $remarks): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        $milestone = StudentMilestone::where('id', $milestoneId)
            ->where('student_id', $student->id)
            ->first();

        if (!$milestone) return;

        $milestone->update(['remarks' => $remarks]);

        Notification::make()
            ->title('Remarks saved')
            ->success()
            ->duration(2000)
            ->send();
    }


    /**
     * Update milestone date completed.
     */
    public function updateMilestoneDate(int $milestoneId, ?string $date): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        $milestone = StudentMilestone::where('id', $milestoneId)
            ->where('student_id', $student->id)
            ->first();

        if (!$milestone) return;

        $milestone->update(['date_completed' => $date ?: null]);

        Notification::make()
            ->title('Date saved')
            ->success()
            ->duration(2000)
            ->send();
    }


    public function toggleMilestoneForm(): void
    {
        $this->showMilestoneForm = !$this->showMilestoneForm;
    }


    /**
     * Add an ad-hoc milestone.
     */
    public function addAdHocMilestone(): void
    {
        $student = $this->getStudentRecord();
        if (!$student || empty($this->newMilestoneName)) return;

        StudentMilestone::create([
            'student_id' => $student->id,
            'milestone_template_id' => null,
            'name' => $this->newMilestoneName,
            'category' => $this->newMilestoneCategory,
            'status' => 'pending',
        ]);

        $this->newMilestoneName = '';
        $this->newMilestoneCategory = 'other';
        $this->showMilestoneForm = false;

        Notification::make()
            ->title('Milestone Added')
            ->success()
            ->duration(3000)
            ->send();
    }



    public function confirmDeleteMilestone(int $id): void
    {
        $this->deletingMilestoneId = $id;
    }


    public function deleteMilestone(): void
    {
        $student = $this->getStudentRecord();
        if (!$student || !$this->deletingMilestoneId) return;

        StudentMilestone::where('id', $this->deletingMilestoneId)
            ->where('student_id', $student->id)
            ->delete();

        $this->deletingMilestoneId = null;

        Notification::make()
            ->title('Milestone Deleted')
            ->warning()
            ->duration(3000)
            ->send();
    }


    public function cancelDeleteMilestone(): void
    {
        $this->deletingMilestoneId = null;
    }

}
