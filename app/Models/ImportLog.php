<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ImportLog extends Model
{
    use LogsActivity;

    protected $fillable = [
        'type',
        'filename',
        'import_type',
        'rows_imported',
        'rows_updated',
        'rows_rejected',
        'rows_unchanged',
        'errors',
        'normalized_programs',
        'user_id',
        'results_file',
    ];

    protected $casts = [
        'errors' => 'array',
        'normalized_programs' => 'array',
        'rows_imported' => 'integer',
        'rows_updated' => 'integer',
        'rows_rejected' => 'integer',
        'rows_unchanged' => 'integer',
    ];

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Import log {$eventName}");
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function enrollees()
    {
        return $this->belongsToMany(\App\Models\Enrollee::class, 'import_log_enrollee')
            ->withPivot('action')
            ->withTimestamps();
    }

    public function graduates()
    {
        return $this->belongsToMany(\App\Models\Graduate::class, 'import_log_graduate')
            ->withPivot('action', 'changes')
            ->withTimestamps();
    }

    public function importedEnrollees()
    {
        return $this->enrollees()->wherePivot('action', 'imported');
    }

    public function updatedEnrollees()
    {
        return $this->enrollees()->wherePivot('action', 'updated');
    }

    public function getTotalRowsAttribute(): int
    {
        return $this->rows_imported + $this->rows_updated + $this->rows_unchanged + $this->rows_rejected;
    }
}
