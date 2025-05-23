<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class VehicleType extends Model

{
    use HasFactory;

    protected $fillable = [
        'name', 'capacity', 'description'
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}


// app/Models/VehicleType.php