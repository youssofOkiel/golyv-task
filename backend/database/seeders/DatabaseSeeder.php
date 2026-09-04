<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Bus;
use App\Models\Seat;
use App\Models\Station;
use App\Models\Trip;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $stations = collect([
            ['name' => 'Cairo', 'code' => 'CAI'],
            ['name' => 'Giza', 'code' => 'GIZ'],
            ['name' => 'Al Fayyum', 'code' => 'FAY'],
            ['name' => 'Al Minya', 'code' => 'MIN'],
            ['name' => 'Asyut', 'code' => 'ASY'],
        ])->mapWithKeys(function (array $data) {
            $station = Station::query()->create($data);

            return [$data['code'] => $station];
        });

        $bus = Bus::query()->create(['name' => 'Golyv Express 12']);

        foreach (range(1, 12) as $number) {
            Seat::query()->create([
                'bus_id' => $bus->id,
                'number' => $number,
            ]);
        }

        $trip = Trip::query()->create([
            'bus_id' => $bus->id,
            'name' => 'Cairo → Asyut (via Al Fayyum & Al Minya)',
        ]);

        foreach (['CAI' => 1, 'FAY' => 2, 'MIN' => 3, 'ASY' => 4] as $code => $sequence) {
            $trip->stations()->attach($stations[$code]->id, ['sequence' => $sequence]);
        }

        $busTwo = Bus::query()->create(['name' => 'Nile Connector']);
        foreach (range(1, 12) as $number) {
            Seat::query()->create([
                'bus_id' => $busTwo->id,
                'number' => $number,
            ]);
        }

        $tripTwo = Trip::query()->create([
            'bus_id' => $busTwo->id,
            'name' => 'Cairo → Asyut (via Giza)',
        ]);

        foreach (['CAI' => 1, 'GIZ' => 2, 'MIN' => 3, 'ASY' => 4] as $code => $sequence) {
            $tripTwo->stations()->attach($stations[$code]->id, ['sequence' => $sequence]);
        }

        $seatFive = Seat::query()->where('bus_id', $bus->id)->where('number', 5)->firstOrFail();

        Booking::query()->create([
            'trip_id' => $trip->id,
            'seat_id' => $seatFive->id,
            'start_station_id' => $stations['CAI']->id,
            'end_station_id' => $stations['MIN']->id,
            'start_sequence' => 1,
            'end_sequence' => 3,
            'customer_name' => 'Sample Passenger',
            'customer_email' => 'sample@golyv.example',
        ]);
    }
}
