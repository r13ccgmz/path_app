<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcademicYear extends Model
{
    use LogsActivity;

    protected $fillable = [
        'year_start',
        'year_end',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Academic year {$eventName}");
    }

    // ── Relationships ──

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    // ── Accessors ──

    public function getLabelAttribute(): string
    {
        return "{$this->year_start}-{$this->year_end}";
    }

    // ── Model Events ──

    protected static function booted(): void
    {
        static::saving(function (AcademicYear $ay) {
            if ($ay->is_current) {
                static::where('id', '!=', $ay->id ?? 0)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }
        });
    }
}
