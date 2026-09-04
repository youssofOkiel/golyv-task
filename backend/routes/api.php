<?php

use App\Http\Controllers\Api\AvailableSeatController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/{trip}', [TripController::class, 'show']);
Route::get('/trips/{trip}/available-seats', AvailableSeatController::class);
Route::post('/bookings', [BookingController::class, 'store'])
    ->middleware('throttle:bookings');
