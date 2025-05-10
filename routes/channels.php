<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('booking.{id}', function ($user, $id) {
    // Allow access if the user is the booking creator or an approver
    $booking = \App\Models\Booking::find($id);
    
    if (!$booking) {
        return false;
    }
    
    if ($booking->user_id === $user->id) {
        return true;
    }
    
    return $booking->approvals()
        ->where('approver_id', $user->id)
        ->exists();
});

Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
