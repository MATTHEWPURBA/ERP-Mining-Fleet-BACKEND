<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Maintenance extends Model
{
    use HasFactory;

    // Explicitly define the actual table name with correct case
    // This overrides Laravel's default table name convention
    protected $table = 'maintenance'; // or 'Maintenance' or whatever the actual casing is
    
    // Specify table explicitly with quoting to handle case-sensitivity
    // If you're not sure about the exact casing, this approach is safer
    // protected $table = '"maintenance"';

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
