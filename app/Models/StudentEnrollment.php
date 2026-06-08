<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentEnrollment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'student_id',
        'student_program_id',
        'course_offering_id',
        'course_id',
        'semester_id',
        'grade',
        'grade_numeric',
        'units_earned',
        'status',
        'course_type_override',
        'remarks',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'grade_numeric' => 'decimal:2',
            'units_earned' => 'integer',
        ];
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Student enrollment {$eventName}");
    }

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function studentProgram(): BelongsTo
    {
        return $this->belongsTo(StudentProgram::class);
    }

    // ── Scopes ──

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForSemester($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }
}
