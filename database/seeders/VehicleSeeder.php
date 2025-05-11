<?php

namespace Database\Seeders;

use App\Models\Vehicle; // Add this import
use App\Models\Location; // Add this import
use App\Models\VehicleType; // Add this import
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::all();
        $vehicleTypes = VehicleType::all();
        $statuses = ['Available', 'Maintenance'];
        
        foreach ($locations as $location) {
            // Each location gets several vehicles
            $vehicleCount = ($location->type == 'HQ') ? 10 : 
                            (($location->type == 'Branch') ? 8 : 5);
            
            for ($i = 1; $i <= $vehicleCount; $i++) {
                $vehicleType = $vehicleTypes->random();
                $isRented = (mt_rand(1, 10) > 8); // 20% chance of being rented
                
                Vehicle::create([
                    'registration_no' => "B{$location->id}$i" . rand(1000, 9999) . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2),
                    'vehicle_type_id' => $vehicleType->id,
                    'location_id' => $location->id,
                    'status' => $statuses[array_rand($statuses)],
                    'is_rented' => $isRented
                ]);
            }
        }
    }
    
}


// database/seeders/VehicleSeeder.php