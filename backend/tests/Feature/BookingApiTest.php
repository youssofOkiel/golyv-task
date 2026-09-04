<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Bus;
use App\Models\Seat;
use App\Models\Station;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Station> */
    private array $stations;

    private Trip $trip;

    private Seat $seatFive;

    private Seat $seatOne;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoute();
    }

    public function test_lists_trips_with_ordered_stations(): void
    {
        $response = $this->getJson('/api/trips');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Cairo → Asyut')
            ->assertJsonPath('data.0.stations.0.code', 'CAI')
            ->assertJsonPath('data.0.stations.3.code', 'ASY')
            ->assertJsonPath('data.0.bus.seat_count', 12);
    }

    public function test_returns_available_seats_for_segment(): void
    {
        Booking::query()->create([
            'trip_id' => $this->trip->id,
            'seat_id' => $this->seatFive->id,
            'start_station_id' => $this->stations['CAI']->id,
            'end_station_id' => $this->stations['MIN']->id,
            'start_sequence' => 1,
            'end_sequence' => 3,
            'customer_name' => 'Existing',
            'customer_email' => 'existing@example.com',
        ]);

        $response = $this->getJson(sprintf(
            '/api/trips/%d/available-seats?start_station_id=%d&end_station_id=%d',
            $this->trip->id,
            $this->stations['CAI']->id,
            $this->stations['FAY']->id,
        ));

        $response->assertOk();

        $seats = collect($response->json('data.seats'));
        $this->assertFalse($seats->firstWhere('number', 5)['available']);
        $this->assertTrue($seats->firstWhere('number', 1)['available']);
    }

    public function test_books_an_available_seat(): void
    {
        $response = $this->postJson('/api/bookings', [
            'trip_id' => $this->trip->id,
            'seat_id' => $this->seatOne->id,
            'start_station_id' => $this->stations['CAI']->id,
            'end_station_id' => $this->stations['ASY']->id,
            'customer_name' => 'Youssof Okiel',
            'customer_email' => 'youssof@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.seat.number', 1)
            ->assertJsonPath('data.customer_email', 'youssof@example.com');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_prevents_overlapping_bookings(): void
    {
        $this->postJson('/api/bookings', [
            'trip_id' => $this->trip->id,
            'seat_id' => $this->seatFive->id,
            'start_station_id' => $this->stations['CAI']->id,
            'end_station_id' => $this->stations['MIN']->id,
            'customer_name' => 'First',
            'customer_email' => 'first@example.com',
        ])->assertCreated();

        $this->postJson('/api/bookings', [
            'trip_id' => $this->trip->id,
            'seat_id' => $this->seatFive->id,
            'start_station_id' => $this->stations['FAY']->id,
            'end_station_id' => $this->stations['ASY']->id,
            'customer_name' => 'Second',
            'customer_email' => 'second@example.com',
        ])->assertStatus(409)
            ->assertJsonPath('error', 'seat_unavailable');
    }

    public function test_allows_same_seat_for_non_overlapping_segments(): void
    {
        $this->postJson('/api/bookings', [
            'trip_id' => $this->trip->id,
            'seat_id' => $this->seatFive->id,
            'start_station_id' => $this->stations['CAI']->id,
            'end_station_id' => $this->stations['MIN']->id,
            'customer_name' => 'First',
            'customer_email' => 'first@example.com',
        ])->assertCreated();

        $this->postJson('/api/bookings', [
            'trip_id' => $this->trip->id,
            'seat_id' => $this->seatFive->id,
            'start_station_id' => $this->stations['MIN']->id,
            'end_station_id' => $this->stations['ASY']->id,
            'customer_name' => 'Second',
            'customer_email' => 'second@example.com',
        ])->assertCreated();

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_rejects_invalid_station_order(): void
    {
        $this->getJson(sprintf(
            '/api/trips/%d/available-seats?start_station_id=%d&end_station_id=%d',
            $this->trip->id,
            $this->stations['ASY']->id,
            $this->stations['CAI']->id,
        ))->assertStatus(422)
            ->assertJsonPath('error', 'invalid_trip_segment');
    }

    public function test_rejects_station_not_on_trip(): void
    {
        $giza = Station::query()->create(['name' => 'Giza', 'code' => 'GIZ']);

        $this->getJson(sprintf(
            '/api/trips/%d/available-seats?start_station_id=%d&end_station_id=%d',
            $this->trip->id,
            $this->stations['CAI']->id,
            $giza->id,
        ))->assertStatus(422)
            ->assertJsonPath('error', 'invalid_trip_segment');
    }

    public function test_rejects_missing_trip(): void
    {
        $this->getJson(sprintf(
            '/api/trips/%d/available-seats?start_station_id=%d&end_station_id=%d',
            9999,
            $this->stations['CAI']->id,
            $this->stations['ASY']->id,
        ))->assertNotFound();
    }

    public function test_overlapping_bookings_only_one_succeeds(): void
    {
        $payload = [
            'trip_id' => $this->trip->id,
            'seat_id' => $this->seatFive->id,
            'start_station_id' => $this->stations['CAI']->id,
            'end_station_id' => $this->stations['ASY']->id,
            'customer_name' => 'Racer',
            'customer_email' => 'racer@example.com',
        ];

        $results = [];

        DB::transaction(function () use ($payload, &$results) {
            $results[] = $this->postJson('/api/bookings', $payload)->status();
        });

        $results[] = $this->postJson('/api/bookings', array_merge($payload, [
            'customer_email' => 'racer2@example.com',
        ]))->status();

        $this->assertContains(201, $results);
        $this->assertContains(409, $results);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_rate_limits_booking_attempts(): void
    {
        $payload = [
            'trip_id' => $this->trip->id,
            'seat_id' => $this->seatOne->id,
            'start_station_id' => $this->stations['CAI']->id,
            'end_station_id' => $this->stations['ASY']->id,
            'customer_name' => 'Spammer',
            'customer_email' => 'spam@example.com',
        ];

        foreach (range(1, 10) as $attempt) {
            $this->postJson('/api/bookings', array_merge($payload, [
                'customer_email' => "spam{$attempt}@example.com",
            ]));
        }

        $this->postJson('/api/bookings', array_merge($payload, [
            'customer_email' => 'spam11@example.com',
        ]))->assertStatus(429);
    }

    private function seedRoute(): void
    {
        $this->stations = collect([
            ['name' => 'Cairo', 'code' => 'CAI'],
            ['name' => 'Al Fayyum', 'code' => 'FAY'],
            ['name' => 'Al Minya', 'code' => 'MIN'],
            ['name' => 'Asyut', 'code' => 'ASY'],
        ])->mapWithKeys(function (array $data) {
            $station = Station::query()->create($data);

            return [$data['code'] => $station];
        })->all();

        $bus = Bus::query()->create(['name' => 'Test Bus']);

        foreach (range(1, 12) as $number) {
            Seat::query()->create(['bus_id' => $bus->id, 'number' => $number]);
        }

        $this->trip = Trip::query()->create([
            'bus_id' => $bus->id,
            'name' => 'Cairo → Asyut',
        ]);

        foreach (['CAI' => 1, 'FAY' => 2, 'MIN' => 3, 'ASY' => 4] as $code => $sequence) {
            $this->trip->stations()->attach($this->stations[$code]->id, ['sequence' => $sequence]);
        }

        $this->seatFive = Seat::query()->where('bus_id', $bus->id)->where('number', 5)->firstOrFail();
        $this->seatOne = Seat::query()->where('bus_id', $bus->id)->where('number', 1)->firstOrFail();
    }
}
