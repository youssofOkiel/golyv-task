<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'trip_id',
        'seat_id',
        'start_station_id',
        'end_station_id',
        'start_sequence',
        'end_sequence',
        'customer_name',
        'customer_email',
    ];

    protected function casts(): array
    {
        return [
            'start_sequence' => 'integer',
            'end_sequence' => 'integer',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function startStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'start_station_id');
    }

    public function endStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'end_station_id');
    }

    /** Half-open intervals [start, end) overlap when A.start < B.end && B.start < A.end. */
    public function overlaps(int $startSequence, int $endSequence): bool
    {
        return $this->start_sequence < $endSequence
            && $startSequence < $this->end_sequence;
    }
}
