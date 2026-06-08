<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EnrollmentCourse extends Model
{
    use LogsActivity;

    protected $table = 'enrollment_courses';

    protected $fillable = [
        'course_code',
    ];

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Enrollment course {$eventName}");
    }

    public function enrollees()
    {
        return $this->belongsToMany(Enrollee::class, 'enrollment_course_enrollee')
            ->withPivot('notes')
            ->withTimestamps();
    }
}
