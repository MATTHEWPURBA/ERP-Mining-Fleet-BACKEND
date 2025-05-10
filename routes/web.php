<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TripController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\PointOfInterestController;





/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// Basic welcome page
Route::get('/', function () {
    return view('welcome');
});

// Sanctum CSRF endpoint for SPA authentication
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

// SPA fallback - This redirects all other requests to the Vue app for client-side routing
Route::get('{any}', function () {
    return view('welcome');
})->where('any', '.*');




// This file is part of the Laravel framework.
// routes/web.php