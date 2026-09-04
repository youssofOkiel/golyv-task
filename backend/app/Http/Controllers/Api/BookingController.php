<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
    ) {}

    #[OA\Post(
        path: '/api/bookings',
        operationId: 'createBooking',
        summary: 'Book a seat for a trip segment',
        tags: ['Bookings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreBookingRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Booking created',
                content: new OA\JsonContent(ref: '#/components/schemas/BookingResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'Seat unavailable for the requested segment',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
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
            new OA\Response(
                response: 429,
                description: 'Too many booking attempts from this IP (10 per minute)',
            ),
        ],
    )]
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookings->book($request->validated());
        $booking->load(['seat', 'startStation', 'endStation']);

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }
}
