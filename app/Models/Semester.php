<?php

namespace App\Models;

use App\Enums\SemesterPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Semester extends Model
{
    protected $fillable = [
        'academic_year_id',
        'semester_period',
        'term_code',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'semester_period' => SemesterPeriod::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // ── Accessors ──

    public function getLabelAttribute(): string
    {
        $period = $this->semester_period?->label() ?? $this->semester_period;
        $ay = $this->academicYear?->label ?? '';
        return "AY {$ay} — {$period}";
    }

    public function getShortLabelAttribute(): string
    {
        $period = str_replace(' Semester', ' Sem', $this->semester_period?->label() ?? $this->semester_period ?? '');
        $ay = $this->academicYear?->label ?? '';
        return "{$ay} ({$period})";
    }

    // ── Model Events ──

    protected static function booted(): void
    {
        static::saving(function (Semester $sem) {
            if ($sem->is_current) {
                static::where('id', '!=', $sem->id ?? 0)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }
        });
    }
}
