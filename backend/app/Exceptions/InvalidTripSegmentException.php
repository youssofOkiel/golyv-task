<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidTripSegmentException extends Exception
{
    public function __construct(string $message = 'Invalid trip segment.')
    {
        parent::__construct($message, 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'invalid_trip_segment',
        ], 422);
    }
}
