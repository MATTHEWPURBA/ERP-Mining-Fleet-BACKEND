<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('maintenance:remind', function () {
    $this->comment('Sending maintenance reminders...');
    
    $upcomingMaintenance = \App\Models\Maintenance::whereNotNull('next_date')
        ->where('next_date', '>=', now())
        ->where('next_date', '<=', now()->addDays(7))
        ->with('vehicle')
        ->get();
        
    foreach ($upcomingMaintenance as $maintenance) {
        // Notify maintenance team or administrators
        \Illuminate\Support\Facades\Log::info("Maintenance reminder for vehicle {$maintenance->vehicle->registration_no} due on {$maintenance->next_date}");
    }
    
    $this->info("Sent {$upcomingMaintenance->count()} maintenance reminders.");
})->purpose('Send reminders for upcoming vehicle maintenance');
