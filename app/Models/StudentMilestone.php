<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentMilestone extends Model
{
    use LogsActivity;

    protected $fillable = [
        'student_id',
        'milestone_template_id',
        'name',
        'category',
        'status',
        'date_started',
        'date_completed',
        'semester_id',
        'remarks',
        'supporting_document',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'date_started' => 'date',
            'date_completed' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Student milestone {$eventName}");
    }

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function milestoneTemplate(): BelongsTo
    {
        return $this->belongsTo(MilestoneTemplate::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Accessors ──

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'coursework' => 'Coursework',
            'examination' => 'Examination',
            'research' => 'Research',
            'publication' => 'Publication',
            'defense' => 'Defense',
            'other' => 'Other',
            default => ucfirst($this->category ?? 'Other'),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'in-progress' => 'In Progress',
            'completed' => 'Completed',
            'waived' => 'Waived',
            default => ucfirst($this->status ?? 'Pending'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'in-progress' => 'warning',
            'completed' => 'success',
            'waived' => 'info',
            default => 'gray',
        };
    }
}
