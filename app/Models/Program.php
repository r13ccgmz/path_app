<?php

namespace App\Models;

use App\Enums\DegreeLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Program extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'degree_level',
        'total_units_required',
        'total_units_override',
        'min_units_per_type',
        'max_residency_years',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'degree_level' => DegreeLevel::class,
            'min_units_per_type' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function majors(): HasMany
    {
        return $this->hasMany(ProgramMajor::class);
    }

    public function programCourses(): HasMany
    {
        return $this->hasMany(ProgramCourse::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ProgramRequirement::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'program_courses')
            ->withPivot('program_major_id', 'cognate_field_id', 'applies_to_all_majors', 'year_level', 'semester_recommended', 'is_required')
            ->withTimestamps();
    }
}
