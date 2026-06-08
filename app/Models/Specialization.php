<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Specialization extends Model
{
    use LogsActivity;

    protected $fillable = ['name'];

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Specialization record {$eventName}");
    }

    public function faculty(): BelongsToMany
    {
        return $this->belongsToMany(
            Faculty::class,
            'faculty_specializations',
            'specialization_id',
            'faculty_id'
        )->withTimestamps();
    }
}
