<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



// app/Models/BookingApproval.php
class BookingApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'approver_id', 'level', 'status', 'comments'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}

// app/Models/BookingApproval.php


// backend/App/Models/BookingApproval.php