<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'program_id',
        'program_major_id',
        'raw_degree_name',
        'admission_semester_id',
        'admission_date',
        'status',
        'graduation_date',
        'gwa',
        'total_units_earned',
        'residency_enrolled',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'graduation_date' => 'date',
        'gwa' => 'decimal:4',
    ];

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function programMajor(): BelongsTo
    {
        return $this->belongsTo(ProgramMajor::class);
    }

    public function admissionSemester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'admission_semester_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    // ── Accessors ──

    /**
     * Get the admission label using semester model accessor or term code fallback.
     */
    public function getAdmissionLabelAttribute(): ?string
    {
        return $this->admissionSemester?->label;
    }

    /**
     * Check if the student has exceeded max residency for this program.
     */
    public function getResidencyExceededAttribute(): bool
    {
        $maxSemesters = ($this->program->max_residency_years ?? 3) * 2;
        return $this->residency_enrolled > $maxSemesters;
    }
}
