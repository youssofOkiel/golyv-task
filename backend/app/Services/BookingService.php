<?php

namespace App\Services;

use App\Exceptions\InvalidTripSegmentException;
use App\Exceptions\SeatUnavailableException;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private readonly SeatAvailabilityService $availability,
    ) {}

    /**
     * @param  array{
     *     trip_id: int,
     *     seat_id: int,
     *     start_station_id: int,
     *     end_station_id: int,
     *     customer_name: string,
     *     customer_email: string
     * }  $data
     */
    public function book(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            /** @var Trip $trip */
            $trip = Trip::query()
                ->with(['stations', 'bus.seats'])
                ->lockForUpdate()
                ->findOrFail($data['trip_id']);

            /** @var Seat $seat */
            $seat = Seat::query()
                ->lockForUpdate()
                ->findOrFail($data['seat_id']);

            if ($seat->bus_id !== $trip->bus_id) {
                throw new InvalidTripSegmentException('The selected seat does not belong to this trip\'s bus.');
            }

            $segment = $this->availability->resolveSegment(
                $trip,
                $data['start_station_id'],
                $data['end_station_id'],
            );

            Booking::query()
                ->where('trip_id', $trip->id)
                ->where('seat_id', $seat->id)
                ->lockForUpdate()
                ->get();

            if (! $this->availability->isSeatAvailable(
                $trip,
                $seat,
                $segment['start_sequence'],
                $segment['end_sequence'],
            )) {
                throw new SeatUnavailableException;
            }

            return Booking::query()->create([
                'trip_id' => $trip->id,
                'seat_id' => $seat->id,
                'start_station_id' => $data['start_station_id'],
                'end_station_id' => $data['end_station_id'],
                'start_sequence' => $segment['start_sequence'],
                'end_sequence' => $segment['end_sequence'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
            ]);
        });
    }
}
