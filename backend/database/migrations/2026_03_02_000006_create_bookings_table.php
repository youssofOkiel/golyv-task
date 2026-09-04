<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->restrictOnDelete();
            $table->foreignId('seat_id')->constrained()->restrictOnDelete();
            $table->foreignId('start_station_id')->constrained('stations')->restrictOnDelete();
            $table->foreignId('end_station_id')->constrained('stations')->restrictOnDelete();
            $table->unsignedInteger('start_sequence');
            $table->unsignedInteger('end_sequence');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->timestamps();

            $table->index(['trip_id', 'seat_id']);
            $table->index(['trip_id', 'seat_id', 'start_sequence', 'end_sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
