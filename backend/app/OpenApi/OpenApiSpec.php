<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Golyv Fleet Booking API',
    description: 'REST API for listing trips, checking seat availability, and booking seats on fleet routes.',
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Application server',
)]
#[OA\Tag(name: 'Trips', description: 'Trip listing and details')]
#[OA\Tag(name: 'Seats', description: 'Seat availability for a trip segment')]
#[OA\Tag(name: 'Bookings', description: 'Create bookings')]
class OpenApiSpec {}
