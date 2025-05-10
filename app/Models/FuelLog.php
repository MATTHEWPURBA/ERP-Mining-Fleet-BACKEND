<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'liters', 'cost', 'odometer', 'date', 'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'liters' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

// app/Models/FuelLog.php
