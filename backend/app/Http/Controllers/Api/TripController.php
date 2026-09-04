<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class TripController extends Controller
{
    #[OA\Get(
        path: '/api/trips',
        operationId: 'listTrips',
        summary: 'List all trips',
        tags: ['Trips'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trips with ordered stations and bus seat count',
                content: new OA\JsonContent(ref: '#/components/schemas/TripCollection'),
            ),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        $trips = Trip::query()
            ->with(['bus.seats', 'stations'])
            ->orderBy('id')
            ->get();

        return TripResource::collection($trips);
    }

    #[OA\Get(
        path: '/api/trips/{trip}',
        operationId: 'showTrip',
        summary: 'Get a single trip',
        tags: ['Trips'],
        parameters: [
            new OA\Parameter(
                name: 'trip',
                description: 'Trip ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1,
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trip details',
                content: new OA\JsonContent(ref: '#/components/schemas/TripResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Trip not found',
            ),
        ],
    )]
    public function show(Trip $trip): TripResource
    {
        $trip->load(['bus.seats', 'stations']);

        return new TripResource($trip);
    }
}
