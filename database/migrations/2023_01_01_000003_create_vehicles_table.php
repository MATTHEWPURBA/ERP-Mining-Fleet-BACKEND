<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_no')->unique();
            $table->foreignId('vehicle_type_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->string('status'); // Available, Booked, Maintenance
            $table->boolean('is_rented')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

// database/migrations/2023_01_01_000003_create_vehicles_table.php