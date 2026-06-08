<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcademicOutputCommittee extends Model
{
    use LogsActivity;

    protected $table = 'academic_output_committee';

    protected $fillable = [
        'academic_output_id',
        'faculty_id',
        'name',
        'role',
        'appointed_date',
        'term_start_id',
        'term_end_id',
    ];

    protected function casts(): array
    {
        return [
            'appointed_date' => 'date',
        ];
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Academic output committee {$eventName}");
    }

    // ── Relationships ──

    public function academicOutput(): BelongsTo
    {
        return $this->belongsTo(AcademicOutput::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function termStart(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'term_start_id');
    }

    public function termEnd(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'term_end_id');
    }

    // ── Accessors ──

    /**
     * Display name: prefer faculty full_name, fallback to free-text name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->faculty) {
            return $this->faculty->full_name;
        }
        return $this->name ?? 'Unknown';
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'adviser' => 'Adviser',
            'co-adviser' => 'Co-Adviser',
            'chair' => 'Chair',
            'co-chair' => 'Co-Chair',
            'member' => 'Member',
            default => ucfirst($this->role ?? 'Unknown'),
        };
    }
}
