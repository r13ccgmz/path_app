<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentCommitteeMember extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Committee member {$eventName}");
    }

    protected $fillable = [
        'student_id',
        'faculty_id',
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

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'co-adviser' => 'Co-Adviser',
            'chair' => 'Chair',
            'co-chair' => 'Co-Chair',
            'member' => 'Member',
            'panel-member' => 'Panel Member',
            default => ucfirst($this->role ?? ''),
        };
    }

    /**
     * Display name: uses the linked Faculty name if available.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->faculty?->full_name ?? 'Unknown Faculty';
    }
}
