<?php

namespace App\Listeners;

use App\Services\ExpoNotificationService;

class NotifyClientOnBookingStatusUpdated
{
    public function __construct(protected ExpoNotificationService $expo) {}

    public function handle(object $event): void
    {
        $booking = $event->booking;

        // Resolve the client's User model
        $clientUser = $booking->client?->user ?? null;

        if (!$clientUser || empty((string) $clientUser->expo_token)) {
            return;
        }

        $status = ucfirst($booking->status ?? 'updated');

        $messages = [
            'pending'   => ['⏳ Booking Pending',    'Your booking is awaiting confirmation.'],
            'accepted'  => ['✅ Booking Accepted',    'Great news! Your booking has been accepted.'],
            'rejected'  => ['❌ Booking Rejected',    'Your booking was not accepted this time.'],
            'completed' => ['🎉 Booking Completed',   'Your booking has been completed. Thank you!'],
            'cancelled' => ['🚫 Booking Cancelled',   'Your booking has been cancelled.'],
        ];

        [$title, $body] = $messages[$booking->status] ?? ["📌 Booking {$status}", "Your booking status changed to {$status}."];

        $this->expo->send(
            (string) $clientUser->expo_token,
            $title,
            $body,
            ['type' => 'booking_status', 'booking_id' => $booking->id, 'status' => $booking->status]
        );
    }
}
