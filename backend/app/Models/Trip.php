<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    protected $fillable = ['bus_id', 'name'];

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'trip_station')
            ->withPivot('sequence')
            ->withTimestamps()
            ->orderByPivot('sequence');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function sequenceFor(int $stationId): ?int
    {
        $pivot = $this->stations->firstWhere('id', $stationId);

        return $pivot?->pivot?->sequence;
    }
}
