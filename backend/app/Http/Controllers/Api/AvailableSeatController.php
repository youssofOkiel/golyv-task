<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableSeatsRequest;
use App\Models\Trip;
use App\Services\SeatAvailabilityService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AvailableSeatController extends Controller
{
    public function __construct(
        private readonly SeatAvailabilityService $availability,
    ) {}

    #[OA\Get(
        path: '/api/trips/{trip}/available-seats',
        operationId: 'availableSeats',
        summary: 'List seat availability for a trip segment',
        tags: ['Seats'],
        parameters: [
            new OA\Parameter(
                name: 'trip',
                description: 'Trip ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1,
            ),
            new OA\Parameter(
                name: 'start_station_id',
                description: 'Boarding station ID (must be on the trip)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1,
            ),
            new OA\Parameter(
                name: 'end_station_id',
                description: 'Alighting station ID (must be after start on the trip)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 4,
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Seat availability for the requested segment',
                content: new OA\JsonContent(ref: '#/components/schemas/AvailableSeatsResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'Trip not found',
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid trip segment or validation error',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(ref: '#/components/schemas/ErrorResponse'),
                        new OA\Schema(ref: '#/components/schemas/ValidationErrorResponse'),
                    ],
                ),
            ),
        ],
    )]
    public function __invoke(AvailableSeatsRequest $request, Trip $trip): JsonResponse
    {
        $seats = $this->availability->availableSeats(
            $trip,
            (int) $request->validated('start_station_id'),
            (int) $request->validated('end_station_id'),
        );

        return response()->json([
            'data' => [
                'trip_id' => $trip->id,
                'start_station_id' => (int) $request->validated('start_station_id'),
                'end_station_id' => (int) $request->validated('end_station_id'),
                'seats' => $seats,
            ],
        ]);
    }
}
