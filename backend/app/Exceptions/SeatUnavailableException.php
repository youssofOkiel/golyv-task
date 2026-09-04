<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class SeatUnavailableException extends Exception
{
    public function __construct(string $message = 'The selected seat is no longer available for this trip segment.')
    {
        parent::__construct($message, 409);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'seat_unavailable',
        ], 409);
    }
}
