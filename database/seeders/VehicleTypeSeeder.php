<?php

namespace Database\Seeders;

use App\Models\VehicleType; // Add this import
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VehicleType::create([
            'name' => 'Pickup Truck',
            'capacity' => 5,
            'description' => 'Standard pickup truck for personnel and light cargo'
        ]);
        
        VehicleType::create([
            'name' => 'SUV',
            'capacity' => 7,
            'description' => 'Sport utility vehicle for personnel transport'
        ]);
        
        VehicleType::create([
            'name' => 'Van',
            'capacity' => 12,
            'description' => 'Passenger van for group transport'
        ]);
        
        VehicleType::create([
            'name' => 'Truck',
            'capacity' => 2,
            'description' => 'Heavy duty truck for equipment transport'
        ]);
        
        VehicleType::create([
            'name' => 'Bus',
            'capacity' => 30,
            'description' => 'Staff transport bus'
        ]);
    }
    
}


// database/seeders/VehicleTypeSeeder.php