<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Station extends Model
{
    protected $fillable = ['name', 'code'];

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class, 'trip_station')
            ->withPivot('sequence')
            ->withTimestamps();
    }
}
