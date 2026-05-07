<?php

use NotificationChannels\Expo\ExpoMessage;
use NotificationChannels\Expo\ExpoChannel;

class NewBookingNotification extends Notification
{
    protected $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return [ExpoChannel::class];
    }

    public function toExpo($notifiable)
    {
        return ExpoMessage::create()
            ->title("New Booking!")
            ->body("Booking #{$this->booking->id} from {$this->booking->user_name}")
            ->playSound()
            ->priority('high')
            ->data(['booking_id' => $this->booking->id]);
    }
}