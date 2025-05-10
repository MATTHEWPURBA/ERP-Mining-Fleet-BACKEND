<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_no', 'vehicle_type_id', 'location_id', 'status', 'is_rented'
    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function maintenance()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class);
    }
}


// app/Models/Vehicle.php