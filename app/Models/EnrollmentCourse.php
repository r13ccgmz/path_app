<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentCourse extends Model
{
    protected $table = 'enrollment_courses';

    protected $fillable = [
        'course_code',
    ];

    public function enrollees()
    {
        return $this->belongsToMany(Enrollee::class, 'enrollment_course_enrollee')
            ->withPivot('notes')
            ->withTimestamps();
    }
}
