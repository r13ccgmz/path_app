<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GraduateCommitteeMember extends Model
{
    use LogsActivity;

    protected $fillable = [
        'graduate_id',
        'faculty_id',
        'name',
        'role',
        'match_type',
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
            ->setDescriptionForEvent(fn(string $eventName) => "Graduate committee member {$eventName}");
    }

    // ── Relationships ──

    public function graduate(): BelongsTo
    {
        return $this->belongsTo(Graduate::class);
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
     * Display name: prefer faculty full_name, fallback to raw name.
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
            'Chair' => 'Chair',
            'Co-Chair' => 'Co-Chair',
            'Member' => 'Member',
            'Adviser' => 'Adviser',
            default => ucfirst($this->role ?? 'Unknown'),
        };
    }
}
