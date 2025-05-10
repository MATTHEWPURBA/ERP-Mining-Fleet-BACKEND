<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    /**
     * Create a new notification instance.
     *
     * @param Booking $booking
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Booking Approved')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your vehicle booking request has been approved.')
            ->line('Vehicle: ' . $this->booking->vehicle->registration_no . ' (' . $this->booking->vehicle->vehicleType->name . ')')
            ->line('Purpose: ' . $this->booking->purpose)
            ->line('Start: ' . $this->booking->start_date->format('Y-m-d H:i'))
            ->line('End: ' . $this->booking->end_date->format('Y-m-d H:i'))
            ->action('View Booking', url('/bookings/' . $this->booking->id))
            ->line('Thank you for using the Fleet Management System.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'Your vehicle booking request has been approved.',
            'vehicle' => $this->booking->vehicle->registration_no,
            'start_date' => $this->booking->start_date->format('Y-m-d H:i'),
            'end_date' => $this->booking->end_date->format('Y-m-d H:i'),
        ];
    }
}

