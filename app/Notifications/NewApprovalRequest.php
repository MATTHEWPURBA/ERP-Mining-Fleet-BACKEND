<?php

namespace App\Notifications;

use App\Models\BookingApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApprovalRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected $approval;

    /**
     * Create a new notification instance.
     *
     * @param BookingApproval $approval
     */
    public function __construct(BookingApproval $approval)
    {
        $this->approval = $approval;
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
        $booking = $this->approval->booking;
        
        return (new MailMessage)
            ->subject('New Vehicle Booking Approval Request')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have a new vehicle booking approval request from ' . $booking->user->name . '.')
            ->line('Vehicle: ' . $booking->vehicle->registration_no . ' (' . $booking->vehicle->vehicleType->name . ')')
            ->line('Purpose: ' . $booking->purpose)
            ->line('Start: ' . $booking->start_date->format('Y-m-d H:i'))
            ->line('End: ' . $booking->end_date->format('Y-m-d H:i'))
            ->line('Approval Level: ' . $this->approval->level)
            ->action('Review Request', url('/approvals/' . $this->approval->id))
            ->line('Please review this request at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $booking = $this->approval->booking;
        
        return [
            'approval_id' => $this->approval->id,
            'booking_id' => $booking->id,
            'message' => 'New booking approval request from ' . $booking->user->name,
            'requester' => $booking->user->name,
            'vehicle' => $booking->vehicle->registration_no,
            'start_date' => $booking->start_date->format('Y-m-d H:i'),
            'end_date' => $booking->end_date->format('Y-m-d H:i'),
            'level' => $this->approval->level,
        ];
    }
}
