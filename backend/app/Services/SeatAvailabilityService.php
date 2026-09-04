<?php

namespace App\Services;

use App\Exceptions\InvalidTripSegmentException;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\Trip;
use Illuminate\Support\Collection;

class SeatAvailabilityService
{
    /** @return array{start_sequence: int, end_sequence: int} */
    public function resolveSegment(Trip $trip, int $startStationId, int $endStationId): array
    {
        $trip->loadMissing('stations');

        $startSequence = $trip->sequenceFor($startStationId);
        $endSequence = $trip->sequenceFor($endStationId);

        if ($startSequence === null || $endSequence === null) {
            throw new InvalidTripSegmentException('Both stations must belong to the selected trip.');
        }

        if ($startSequence >= $endSequence) {
            throw new InvalidTripSegmentException('The start station must come before the end station on the route.');
        }

        return [
            'start_sequence' => $startSequence,
            'end_sequence' => $endSequence,
        ];
    }

    public function availableSeats(Trip $trip, int $startStationId, int $endStationId): Collection
    {
        $segment = $this->resolveSegment($trip, $startStationId, $endStationId);
        $trip->loadMissing('bus.seats');

        $bookings = Booking::query()
            ->where('trip_id', $trip->id)
            ->get()
            ->groupBy('seat_id');

        return $trip->bus->seats
            ->sortBy('number')
            ->values()
            ->map(function (Seat $seat) use ($bookings, $segment) {
                $seatBookings = $bookings->get($seat->id, collect());
                $available = $seatBookings->every(
                    fn (Booking $booking) => ! $booking->overlaps(
                        $segment['start_sequence'],
                        $segment['end_sequence'],
                    )
                );

                return [
                    'id' => $seat->id,
                    'number' => $seat->number,
                    'available' => $available,
                ];
            });
    }

    public function isSeatAvailable(Trip $trip, Seat $seat, int $startSequence, int $endSequence): bool
    {
        $bookings = Booking::query()
            ->where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->get();

        return $bookings->every(
            fn (Booking $booking) => ! $booking->overlaps($startSequence, $endSequence)
        );
    }
}
