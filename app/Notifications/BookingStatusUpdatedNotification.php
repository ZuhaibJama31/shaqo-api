<?php


namespace App\Notifications;
use Illuminate\Notifications\Notification;

class BookingStatusUpdatedNotification extends Notification
{
    public $booking;

    // ✅ ADD THIS
    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Booking Update',
            'message' => "Your booking is now {$this->booking->status}",
            'booking_id' => $this->booking->id,
        ];
    }
}