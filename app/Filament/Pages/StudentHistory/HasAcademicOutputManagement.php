<?php

namespace App\Filament\Pages\StudentHistory;

use App\Models\AcademicOutput;
use App\Models\AcademicOutputCommittee;
use App\Models\Student;
use Filament\Notifications\Notification;

/**
 * HasAcademicOutputManagement trait extracted from StudentHistory.
 */
trait HasAcademicOutputManagement
{

    // ══════════════════════════════════════════════════════════
    // ── Concern 4: Academic Output Management ──
    // ══════════════════════════════════════════════════════════

    /**
     * Get the academic output(s) for the current student.
     */
    public function getAcademicOutputs(): array
    {
        $student = $this->getStudentRecord();
        if (!$student) return [];

        $mapAo = function ($ao, string $role = 'primary_author') {
            return [
                'id' => $ao->id,
                'title' => $ao->title,
                'type' => $ao->type,
                'type_other_description' => $ao->type_other_description,
                'type_label' => $ao->type_label,
                'status' => $ao->status,
                'abstract' => $ao->abstract,
                'keywords' => $ao->keywords,
                'proposal_defense_date' => $ao->proposal_defense_date?->format('F j, Y'),
                'proposal_defense_date_raw' => $ao->proposal_defense_date?->format('Y-m-d'),
                'proposal_defense_result' => $ao->proposal_defense_result,
                'final_defense_date' => $ao->final_defense_date?->format('F j, Y'),
                'final_defense_date_raw' => $ao->final_defense_date?->format('Y-m-d'),
                'final_defense_result' => $ao->final_defense_result,
                'date_submitted' => $ao->date_submitted?->format('F j, Y'),
                'drive_link' => $ao->drive_link,
                'semester_id' => $ao->semester_id,
                'term_code' => $ao->term_code,
                'semester_label' => $ao->semester?->label,
                'author_role' => $role,
                'is_editable' => $role === 'owner',
                'primary_authors' => collect([$ao->student])
                    ->filter()
                    ->merge($ao->primaryAuthors ?? [])
                    ->map(fn($s) => "{$s->surname}, {$s->given_name}")
                    ->unique()
                    ->toArray(),
                'co_authors' => $ao->coAuthors->map(fn ($s) => "{$s->surname}, {$s->given_name}")->toArray(),
                'committee' => $ao->committeeMembers->load('faculty', 'termStart', 'termEnd')->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->display_name,
                    'designation' => $c->faculty?->designation ?? null,
                    'role' => $c->role,
                    'role_label' => $c->role_label,
                    'appointed_date' => $c->appointed_date,
                    'formatted_appointed_date' => $c->appointed_date ? \Carbon\Carbon::parse($c->appointed_date)->format('F j, Y') : null,
                    'term_start' => $c->termStart?->label ?? null,
                    'term_end' => $c->termEnd?->label ?? null,
                ])->toArray(),
            ];
        };

        // Consolidated: fetch ALL related AOs in a single query (owner + shared + co-authored)
        $allOutputs = AcademicOutput::where('student_id', $student->id)
            ->orWhereHas('students', fn ($q) => $q->where('student_id', $student->id))
            ->with(['committeeMembers.faculty', 'committeeMembers.termStart', 'committeeMembers.termEnd', 'coAuthors', 'primaryAuthors', 'student', 'semester.academicYear'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $allOutputs->map(function ($ao) use ($student, $mapAo) {
            if ($ao->student_id === $student->id) {
                return $mapAo($ao, 'owner');
            }
            // Check if this student is a primary author
            $isPrimary = $ao->students
                ->where('pivot.role', 'primary_author')
                ->contains('id', $student->id);
            return $mapAo($ao, $isPrimary ? 'shared_primary' : 'co_author');
        })->unique(fn ($item) => $item['id'])->values()->toArray();
    }


    public function toggleAoForm(): void
    {
        $this->showAoForm = !$this->showAoForm;
        if (!$this->showAoForm) {
            $this->cancelAo();
        }
    }


    public function startEditAo(int $id): void
    {
        $student = $this->getStudentRecord();
        if (!$student) return;

        $ao = AcademicOutput::where('id', $id)
            ->where('student_id', $student->id)
            ->with('committeeMembers')
            ->first();

        if (!$ao) return;

        // Store editing ID so the action handler knows it's an edit
        $this->editingAoId = $ao->id;

        // Mount the addAcademicOutput action with prefilled data
        $this->mountAction('addAcademicOutput', [
            'title' => $ao->title ?? '',
            'type' => $ao->type,
            'type_other_description' => $ao->type_other_description,
            'drive_link' => $ao->drive_link,
            'status' => $ao->status,
            'abstract' => $ao->abstract,
            'keywords' => $ao->keywords,
            'proposal_defense_date' => $ao->proposal_defense_date?->format('Y-m-d'),
            'proposal_defense_result' => $ao->proposal_defense_result,
            'final_defense_date' => $ao->final_defense_date?->format('Y-m-d'),
            'final_defense_result' => $ao->final_defense_result,
            'date_submitted' => $ao->date_submitted?->format('Y-m-d'),
            'semester_id' => $ao->semester_id,
            'committee_members' => $ao->committeeMembers->map(fn($cm) => [
                'role' => $cm->role,
                'faculty_id' => $cm->faculty_id,
                'appointed_date' => $cm->appointed_date ? \Carbon\Carbon::parse($cm->appointed_date)->format('Y-m-d') : null,
                'term_start_id' => $cm->term_start_id,
                'term_end_id' => $cm->term_end_id,
            ])->toArray(),
            'co_author_ids' => $ao->coAuthors->pluck('id')->toArray(),
            'primary_author_ids' => $ao->primaryAuthors()->where('students.id', '!=', $student->id)->pluck('students.id')->toArray(),
        ]);
    }


    public function saveAo(): void
    {
        abort_if(auth()->user()?->hasRole('viewer'), 403, 'Unauthorized action.');

        $student = $this->getStudentRecord();
        if (!$student || empty($this->aoTitle)) return;

        $data = [
            'student_id' => $student->id,
            'title' => $this->aoTitle,
            'type' => $this->aoType,
            'status' => $this->aoStatus,
            'proposal_defense_date' => $this->aoProposalDefenseDate ?: null,
            'proposal_defense_result' => $this->aoProposalDefenseResult ?: null,
            'final_defense_date' => $this->aoFinalDefenseDate ?: null,
            'final_defense_result' => $this->aoFinalDefenseResult ?: null,
        ];

        if ($this->editingAoId) {
            $ao = AcademicOutput::where('id', $this->editingAoId)
                ->where('student_id', $student->id)
                ->first();
            if ($ao) $ao->update($data);
            $msg = 'Academic Output Updated';
        } else {
            $ao = AcademicOutput::create($data);
            $msg = 'Academic Output Added';
        }

        // Sync committee members
        if ($ao) {
            $ao->committeeMembers()->delete();
            $committeeData = [
                ['role' => 'adviser', 'name' => $this->aoAdviser],
                ['role' => 'co-adviser', 'name' => $this->aoCoAdviser],
                ['role' => 'chair', 'name' => $this->aoChair],
                ['role' => 'co-chair', 'name' => $this->aoCoChair],
                ['role' => 'member', 'name' => $this->aoMember1],
                ['role' => 'member', 'name' => $this->aoMember2],
                ['role' => 'member', 'name' => $this->aoMember3],
            ];
            foreach ($committeeData as $cm) {
                if (!empty(trim($cm['name']))) {
                    AcademicOutputCommittee::create([
                        'academic_output_id' => $ao->id,
                        'name' => trim($cm['name']),
                        'role' => $cm['role'],
                    ]);
                }
            }
        }

        $this->cancelAo();

        Notification::make()
            ->title($msg)
            ->success()
            ->duration(3000)
            ->send();
    }


    public function cancelAo(): void
    {
        $this->editingAoId = null;
        $this->aoTitle = '';
        $this->aoType = 'thesis';
        $this->aoStatus = 'topic-approved';
        $this->aoProposalDefenseDate = null;
        $this->aoProposalDefenseResult = null;
        $this->aoFinalDefenseDate = null;
        $this->aoFinalDefenseResult = null;
        $this->aoAdviser = '';
        $this->aoCoAdviser = '';
        $this->aoChair = '';
        $this->aoCoChair = '';
        $this->aoMember1 = '';
        $this->aoMember2 = '';
        $this->aoMember3 = '';
        $this->showAoForm = false;
    }


    public function confirmDeleteAo(int $id): void
    {
        $this->deletingAoId = $id;
    }


    public function deleteAo(): void
    {
        abort_if(auth()->user()?->hasRole('viewer'), 403, 'Unauthorized action.');

        $student = $this->getStudentRecord();
        if (!$student || !$this->deletingAoId) return;

        AcademicOutput::where('id', $this->deletingAoId)
            ->where('student_id', $student->id)
            ->delete();

        $this->deletingAoId = null;

        Notification::make()
            ->title('Academic Output Deleted')
            ->warning()
            ->duration(3000)
            ->send();
    }


    public function cancelDeleteAo(): void
    {
        $this->deletingAoId = null;
    }

}
