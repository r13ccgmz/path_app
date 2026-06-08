<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Faculty extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Faculty record {$eventName}");
    }

    protected $table = 'faculty';

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'employment_status',
        'designation',
        'unit_id',
        'highest_degree',
        'staff_classification',
        'birthday',
        'sex',
        'date_hired_cpaf',
        'year_graduated',
        'contact_number',
        'is_pursuing_postgrad',
        'postgrad_program',
        'postgrad_level',
        'faculty_status',
        'is_external',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'date_hired_cpaf' => 'date',
            'is_pursuing_postgrad' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(
            Specialization::class ?? Model::class,
            'faculty_specializations',
            'faculty_id',
            'specialization_id'
        )->withTimestamps();
    }

    public function courseOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    /**
     * Students this faculty member advises (via committee membership with 'Adviser' role).
     */
    public function adviseStudents(): HasMany
    {
        return $this->hasMany(StudentCommitteeMember::class, 'faculty_id')
            ->where('role', 'Adviser');
    }

    public function committeeMemberships(): HasMany
    {
        return $this->hasMany(StudentCommitteeMember::class, 'faculty_id');
    }

    /**
     * Students this faculty advises (via StudentCommitteeMember with 'Adviser' role).
     * Used by MentorshipMonitoring, AdviseeDistributionChart, and FacultyResource.
     */
    public function advisees(): HasManyThrough
    {
        return $this->hasManyThrough(
            Student::class,
            StudentCommitteeMember::class,
            'faculty_id',    // FK on student_committee_members
            'id',            // FK on students
            'id',            // local key on faculty
            'student_id'     // local key on student_committee_members
        )->where('student_committee_members.role', 'Adviser');
    }

    /**
     * Graduate committee memberships (from graduate imports).
     */
    public function graduateCommitteeMembers(): HasMany
    {
        return $this->hasMany(GraduateCommitteeMember::class, 'faculty_id');
    }

    /**
     * Academic output committee memberships.
     */
    public function academicOutputCommitteeMembers(): HasMany
    {
        return $this->hasMany(AcademicOutputCommittee::class, 'faculty_id');
    }

    // ── Accessors ──

    public function getFullNameAttribute(): string
    {
        $name = implode(', ', [$this->last_name, $this->first_name]);
        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }
        if ($this->suffix) {
            $name .= ' ' . $this->suffix;
        }
        return $name;
    }

    public function getHighestDegreeLabelAttribute(): string
    {
        return match ($this->highest_degree) {
            'high-school' => 'High School',
            'vocational' => 'Vocational',
            'bachelors' => "Bachelor's",
            'masters' => "Master's",
            'doctorate' => 'Doctorate',
            'n/a' => 'N/A',
            default => ucfirst($this->highest_degree ?? 'Unknown'),
        };
    }

    public function getEmploymentStatusLabelAttribute(): string
    {
        return match ($this->employment_status) {
            'full-time' => 'Full-Time',
            'part-time' => 'Part-Time',
            'temporary' => 'Temporary',
            'others' => 'Others',
            default => ucfirst($this->employment_status ?? 'Unknown'),
        };
    }

    public function getStaffClassificationLabelAttribute(): string
    {
        return match ($this->staff_classification) {
            'admin' => 'Admin',
            'reps' => 'REPS',
            'faculty' => 'Faculty',
            'others' => 'Others',
            default => ucfirst($this->staff_classification ?? 'Unknown'),
        };
    }
}
