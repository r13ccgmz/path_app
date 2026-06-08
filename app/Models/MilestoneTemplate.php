<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MilestoneTemplate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'program_id',
        'applies_to_all_programs',
        'name',
        'description',
        'category',
        'sort_order',
        'is_required',
        'degree_level',
    ];

    protected function casts(): array
    {
        return [
            'applies_to_all_programs' => 'boolean',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Milestone template {$eventName}");
    }

    // ── Relationships ──

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function studentMilestones(): HasMany
    {
        return $this->hasMany(StudentMilestone::class);
    }

    // ── Scopes ──

    /**
     * Get templates applicable to a given program and degree level.
     */
    public function scopeForProgram($query, int $programId, ?string $degreeLevel = null)
    {
        return $query->where(function ($q) use ($programId) {
            $q->where('applies_to_all_programs', true)
              ->orWhere('program_id', $programId);
        })->when($degreeLevel, function ($q) use ($degreeLevel) {
            $q->where(function ($q2) use ($degreeLevel) {
                $q2->whereNull('degree_level')
                   ->orWhere('degree_level', $degreeLevel);
            });
        });
    }

    // ── Helpers ──

    /**
     * Get the display label for the category.
     */
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

    /**
     * Get the display label for the degree level.
     */
    public function getDegreeLevelLabelAttribute(): string
    {
        return match ($this->degree_level) {
            'masters' => "Master's",
            'doctorate' => 'Doctorate',
            default => 'All',
        };
    }
}
