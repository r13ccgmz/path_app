<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcademicOutput extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Academic output {$eventName}");
    }
    protected $fillable = [
        'student_id',
        'semester_id',
        'term_code',
        'title',
        'type',
        'type_other_description',
        'abstract',
        'status',
        'proposal_defense_date',
        'proposal_defense_result',
        'proposal_defense_remarks',
        'final_defense_date',
        'final_defense_result',
        'final_defense_remarks',
        'date_submitted',
        'drive_link',
        'keywords',
    ];

    protected function casts(): array
    {
        return [
            'proposal_defense_date' => 'date',
            'final_defense_date' => 'date',
            'date_submitted' => 'date',
            'keywords' => 'array',
        ];
    }

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(AcademicOutputCommittee::class);
    }

    /**
     * Students who are co-authors of this academic output (via pivot table).
     */
    public function students(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'academic_output_student')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get primary author students (excludes the main owning student).
     */
    public function primaryAuthors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'academic_output_student')
            ->withPivot('role')
            ->wherePivot('role', 'primary_author')
            ->withTimestamps();
    }

    /**
     * Get co-author students.
     */
    public function coAuthors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'academic_output_student')
            ->withPivot('role')
            ->wherePivot('role', 'co_author')
            ->withTimestamps();
    }

    // ── Accessors ──

    public function getTypeLabelAttribute(): string
    {
        if ($this->type === 'others' && !empty($this->type_other_description)) {
            return 'Other: ' . $this->type_other_description;
        }

        return match ($this->type) {
            'thesis' => 'Thesis',
            'dissertation' => 'Dissertation',
            'field-study' => 'Field Study',
            'others' => 'Other',
            default => ucfirst($this->type ?? 'Unknown'),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'topic-approved' => 'Topic Approved',
            'proposal-writing' => 'Proposal Writing',
            'proposal-defended' => 'Proposal Defended',
            'data-collection' => 'Data Collection',
            'writing' => 'Writing',
            'final-defense-scheduled' => 'Final Defense Scheduled',
            'defended' => 'Defended',
            'revising' => 'Revising',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            default => ucfirst($this->status ?? 'Unknown'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'topic-approved' => 'gray',
            'proposal-writing', 'writing' => 'warning',
            'proposal-defended', 'defended' => 'info',
            'data-collection' => 'primary',
            'final-defense-scheduled' => 'warning',
            'revising' => 'warning',
            'submitted' => 'info',
            'approved' => 'success',
            default => 'gray',
        };
    }
}
