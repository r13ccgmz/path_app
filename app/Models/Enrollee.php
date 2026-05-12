<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollee extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_id',
        'campus_id',
        'student_number',
        'last_name',
        'first_name',
        'middle_name',
        'degree_program',
        'courses_enrolled',
        'total_units',
        'sex',
        'marital_status',
        'source',
        'birthdate',
        'nationality',
        'email',
        'enrollment_status',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'total_units' => 'integer',
    ];

    public function getFullNameAttribute(): string
    {
        return implode(', ', [$this->last_name, $this->first_name]) .
            ($this->middle_name ? ' ' . $this->middle_name : '');
    }

    // Format a student number as xxxx-xxxxx for display.
    public static function formatStudentNumber(?string $sn): string
    {
        if (!$sn) return '';
        if (\Illuminate\Support\Str::startsWith($sn, 'TEMP-')) return $sn;
        $snDigits = preg_replace('/[^0-9]/', '', $sn);
        return (strlen($snDigits) === 9) ? substr($snDigits, 0, 4) . '-' . substr($snDigits, 4) : $sn;
    }

    public function scopeByTerm($query, $termId)
    {
        return $query->where('term_id', $termId);
    }

    public function scopeByStudentNumber($query, $studentNumber)
    {
        return $query->where('student_number', $studentNumber);
    }

    public function term()
    {
        return $this->belongsTo(Semester::class, 'term_id', 'term_code');
    }

    public function enrollmentCourses()
    {
        return $this->belongsToMany(EnrollmentCourse::class, 'enrollment_course_enrollee')
            ->withPivot('notes')
            ->withTimestamps();
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    public function importLogs()
    {
        return $this->belongsToMany(ImportLog::class, 'import_log_enrollee')
            ->withPivot('action')
            ->withTimestamps();
    }
}
