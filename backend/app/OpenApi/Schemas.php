<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Station',
    type: 'object',
    required: ['id', 'name', 'code'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Cairo'),
        new OA\Property(property: 'code', type: 'string', example: 'CAI'),
        new OA\Property(property: 'sequence', type: 'integer', example: 1, description: 'Order of the station on the trip'),
    ],
)]
#[OA\Schema(
    schema: 'Bus',
    type: 'object',
    required: ['id', 'name', 'seat_count'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Fleet Bus 1'),
        new OA\Property(property: 'seat_count', type: 'integer', example: 12),
    ],
)]
#[OA\Schema(
    schema: 'Trip',
    type: 'object',
    required: ['id', 'name', 'bus', 'stations'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Cairo → Asyut'),
        new OA\Property(property: 'bus', ref: '#/components/schemas/Bus'),
        new OA\Property(
            property: 'stations',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Station'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'TripCollection',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Trip'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'TripResponse',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Trip'),
    ],
)]
#[OA\Schema(
    schema: 'SeatAvailability',
    type: 'object',
    required: ['id', 'number', 'available'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'number', type: 'integer', example: 5),
        new OA\Property(property: 'available', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'AvailableSeatsData',
    type: 'object',
    required: ['trip_id', 'start_station_id', 'end_station_id', 'seats'],
    properties: [
        new OA\Property(property: 'trip_id', type: 'integer', example: 1),
        new OA\Property(property: 'start_station_id', type: 'integer', example: 1),
        new OA\Property(property: 'end_station_id', type: 'integer', example: 4),
        new OA\Property(
            property: 'seats',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SeatAvailability'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'AvailableSeatsResponse',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/AvailableSeatsData'),
    ],
)]
#[OA\Schema(
    schema: 'StoreBookingRequest',
    type: 'object',
    required: [
        'trip_id',
        'seat_id',
        'start_station_id',
        'end_station_id',
        'customer_name',
        'customer_email',
    ],
    properties: [
        new OA\Property(property: 'trip_id', type: 'integer', example: 1),
        new OA\Property(property: 'seat_id', type: 'integer', example: 1),
        new OA\Property(property: 'start_station_id', type: 'integer', example: 1),
        new OA\Property(property: 'end_station_id', type: 'integer', example: 4),
        new OA\Property(property: 'customer_name', type: 'string', maxLength: 255, example: 'Youssof Okiel'),
        new OA\Property(property: 'customer_email', type: 'string', format: 'email', maxLength: 255, example: 'youssof@example.com'),
    ],
)]
#[OA\Schema(
    schema: 'BookingSeat',
    type: 'object',
    required: ['id', 'number'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'number', type: 'integer', example: 1),
    ],
)]
#[OA\Schema(
    schema: 'Booking',
    type: 'object',
    required: [
        'id',
        'trip_id',
        'seat',
        'start_station',
        'end_station',
        'start_sequence',
        'end_sequence',
        'customer_name',
        'customer_email',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'trip_id', type: 'integer', example: 1),
        new OA\Property(property: 'seat', ref: '#/components/schemas/BookingSeat'),
        new OA\Property(property: 'start_station', ref: '#/components/schemas/Station'),
        new OA\Property(property: 'end_station', ref: '#/components/schemas/Station'),
        new OA\Property(property: 'start_sequence', type: 'integer', example: 1),
        new OA\Property(property: 'end_sequence', type: 'integer', example: 4),
        new OA\Property(property: 'customer_name', type: 'string', example: 'Youssof Okiel'),
        new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'youssof@example.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'BookingResponse',
    type: 'object',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Booking'),
    ],
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    required: ['message', 'error'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The selected seat is no longer available for this trip segment.'),
        new OA\Property(property: 'error', type: 'string', example: 'seat_unavailable'),
    ],
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    type: 'object',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The trip id field is required.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            example: ['trip_id' => ['The trip id field is required.']],
        ),
    ],
)]
class Schemas {}
