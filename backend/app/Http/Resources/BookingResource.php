<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Booking */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'seat' => [
                'id' => $this->seat->id,
                'number' => $this->seat->number,
            ],
            'start_station' => [
                'id' => $this->startStation->id,
                'name' => $this->startStation->name,
                'code' => $this->startStation->code,
            ],
            'end_station' => [
                'id' => $this->endStation->id,
                'name' => $this->endStation->name,
                'code' => $this->endStation->code,
            ],
            'start_sequence' => $this->start_sequence,
            'end_sequence' => $this->end_sequence,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
