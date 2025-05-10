<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'type', 'description', 'cost', 'date', 'next_date'
    ];

    protected $casts = [
        'date' => 'date',
        'next_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}

// app/Models/Maintenance.php
