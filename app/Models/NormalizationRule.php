<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NormalizationRule extends Model
{
    use LogsActivity;

    protected $fillable = [
        'type',
        'from_value',
        'to_value',
    ];

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Normalization rule {$eventName}");
    }

    /**
     * Get all rules of a given type as a from => to map.
     */
    public static function getMap(string $type): array
    {
        return static::where('type', $type)
            ->pluck('to_value', 'from_value')
            ->toArray();
    }
}
