<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

class BookingCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        $client = $this->booking->client;

        return [
            'type'         => 'new_booking',
            'title'        => 'New Booking',
            'message'      => "{$client->name} made a booking",
            'booking_id'   => $this->booking->id,
            'client_id'    => $client->id,
            'client_name'  => $client->name,
            'client_phone' => $client->phone, // ← admin uses this to call
            'scheduled_at' => $this->booking->scheduled_at,
            'address'      => $this->booking->address,
            'city'         => $this->booking->city,
        ];
    }
}