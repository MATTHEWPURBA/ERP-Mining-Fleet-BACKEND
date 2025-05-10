<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $comments;

    /**
     * Create a new notification instance.
     *
     * @param Booking $booking
     * @param string|null $comments
     */
    public function __construct(Booking $booking, ?string $comments = null)
    {
        $this->booking = $booking;
        $this->comments = $comments;
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
        $mail = (new MailMessage)
            ->subject('Vehicle Booking Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your vehicle booking request has been rejected.')
            ->line('Vehicle: ' . $this->booking->vehicle->registration_no . ' (' . $this->booking->vehicle->vehicleType->name . ')')
            ->line('Purpose: ' . $this->booking->purpose)
            ->line('Start: ' . $this->booking->start_date->format('Y-m-d H:i'))
            ->line('End: ' . $this->booking->end_date->format('Y-m-d H:i'));

        if ($this->comments) {
            $mail->line('Reason: ' . $this->comments);
        }

        return $mail->action('View Booking', url('/bookings/' . $this->booking->id))
            ->line('Please contact the fleet management team if you have any questions.');
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
            'message' => 'Your vehicle booking request has been rejected.',
            'vehicle' => $this->booking->vehicle->registration_no,
            'start_date' => $this->booking->start_date->format('Y-m-d H:i'),
            'end_date' => $this->booking->end_date->format('Y-m-d H:i'),
            'comments' => $this->comments,
        ];
    }
}
