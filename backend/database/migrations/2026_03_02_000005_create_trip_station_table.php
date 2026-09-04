<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_station', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->timestamps();

            $table->unique(['trip_id', 'station_id']);
            $table->unique(['trip_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_station');
    }
};
