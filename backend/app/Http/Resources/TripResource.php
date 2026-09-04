<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Trip */
class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bus' => [
                'id' => $this->bus->id,
                'name' => $this->bus->name,
                'seat_count' => $this->bus->seats->count(),
            ],
            'stations' => $this->stations->map(fn ($station) => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'sequence' => $station->pivot->sequence,
            ])->values(),
        ];
    }
}
