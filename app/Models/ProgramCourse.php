<?php

namespace App\Models;

use App\Enums\CourseType;
use App\Enums\SemesterPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProgramCourse extends Model
{
    use LogsActivity;

    protected $fillable = [
        'program_id',
        'course_id',
        'program_major_id',
        'cognate_field_id',
        'course_type',
        'sub_group',
        'semester_offered',
        'applies_to_all_majors',
        'semester_recommended',
        'is_required',
        'description',
        'units',
        'lecture_hours',
        'lab_hours',
        'prerequisite_text',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'course_type' => CourseType::class,
            'applies_to_all_majors' => 'boolean',
            'is_required' => 'boolean',
            'semester_recommended' => SemesterPeriod::class,
            'lecture_hours' => 'decimal:1',
            'lab_hours' => 'decimal:1',
        ];
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Program course {$eventName}");
    }

    // ── Relationships ──

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function programMajor(): BelongsTo
    {
        return $this->belongsTo(ProgramMajor::class);
    }

    public function cognateField(): BelongsTo
    {
        return $this->belongsTo(CognateField::class);
    }
}
