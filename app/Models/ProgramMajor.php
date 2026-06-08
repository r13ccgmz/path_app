<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProgramMajor extends Model
{
    use LogsActivity;

    protected $fillable = [
        'program_id',
        'name',
        'description',
    ];

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Program major {$eventName}");
    }

    // ── Relationships ──

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
