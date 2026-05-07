<?php

namespace App\Notifications;
use Illuminate\Notifications\Notification;

class BookingCreatedNotification extends Notification
{
    public $booking;

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
            'title' => 'New Booking',
            'message' => 'A client created a booking',
            'booking_id' => $this->booking->id,
        ];
    }
}