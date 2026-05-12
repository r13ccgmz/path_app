<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NormalizationRule extends Model
{
    protected $fillable = [
        'type',
        'from_value',
        'to_value',
    ];

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
