<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialization extends Model
{
    protected $fillable = ['name'];

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
