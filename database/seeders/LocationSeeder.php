<?php

namespace Database\Seeders;

use App\Models\Location; // Add this import
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::create([
            'name' => 'Headquarters',
            'address' => '123 Main St, Jakarta',
            'type' => 'HQ',
            'coordinates' => '-6.175110,106.865036'
        ]);
        
        Location::create([
            'name' => 'Branch Office',
            'address' => '456 Commerce Ave, Bandung',
            'type' => 'Branch',
            'coordinates' => '-6.914744,107.609810'
        ]);
        
        // Six mining sites
        for ($i = 1; $i <= 6; $i++) {
            Location::create([
                'name' => "Mining Site $i",
                'address' => "Mining Area $i, Indonesia",
                'type' => 'Mining',
                'coordinates' => $this->getRandomCoordinate()
            ]);
        }
    }

    private function getRandomCoordinate(): string
    {
        // Generate random coordinates in Indonesia
        $lat = mt_rand(-10000000, -5000000) / 1000000;
        $lng = mt_rand(105000000, 115000000) / 1000000;
        return "$lat,$lng";
    }

}

// database/seeders/LocationSeeder.php