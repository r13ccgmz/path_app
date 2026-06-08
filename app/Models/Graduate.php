<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Graduate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'student_number',
        'program_id',
        'match_type',
        'source',
        'semester_graduated',
        'semester_raw',
        'country_of_origin',
        'degree',
        'program_name',
        'major_field_raw',
        'chair',
        'co_chair',
        'member1',
        'member2',
        'member3',
        'member4',
        'member5',
        'committee_data',
        'major',
    ];

    protected $casts = [
        'committee_data' => 'array',
    ];

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Graduate record {$eventName}");
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_graduated', 'term_code');
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(GraduateCommitteeMember::class);
    }
}
