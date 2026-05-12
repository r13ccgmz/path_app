<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramMajor extends Model
{
    protected $fillable = [
        'program_id',
        'name',
        'description',
    ];

    // ── Relationships ──

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
