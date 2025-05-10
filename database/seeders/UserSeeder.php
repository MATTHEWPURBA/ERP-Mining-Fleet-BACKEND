<?php

namespace Database\Seeders;


use App\Models\User; // Add this import
use App\Models\Location; // Add this import
use Illuminate\Support\Facades\Hash; // Also need to import Hash facade
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@miningfleet.com',
            'password' => Hash::make('admin123'),
            'role' => 'Administrator',
            'department' => 'Administration',
            'location_id' => 1 // HQ
        ]);
        
        // Create approver user
        User::create([
            'name' => 'Approver User',
            'email' => 'approver@miningfleet.com',
            'password' => Hash::make('approver123'),
            'role' => 'Approver',
            'department' => 'Operations',
            'location_id' => 1 // HQ
        ]);
        
        // Create regular user
        User::create([
            'name' => 'Regular User',
            'email' => 'user@miningfleet.com',
            'password' => Hash::make('user123'),
            'role' => 'User',
            'department' => 'Logistics',
            'location_id' => 2, // Branch
            'supervisor_id' => 2 // Approver is supervisor
        ]);
        
        // Create additional users for each location
        $locations = Location::all();
        $departments = ['Operations', 'Logistics', 'Management', 'Safety', 'Engineering'];
        
        foreach ($locations as $location) {
            for ($i = 1; $i <= 3; $i++) {
                User::create([
                    'name' => "User {$location->id}-$i",
                    'email' => "user{$location->id}$i@miningfleet.com",
                    'password' => Hash::make('password'),
                    'role' => 'User',
                    'department' => $departments[array_rand($departments)],
                    'location_id' => $location->id,
                    'supervisor_id' => rand(1, 3) // One of the first three users
                ]);
            }
        }
    }
    
}
