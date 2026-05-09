<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ExpoNotificationService;

class NotifyAdminOnBookingCreated
{
    public function __construct(protected ExpoNotificationService $expo) {}

    /**
     * Handle the BookingCreated event (or any event that carries a $booking).
     * Works with both dedicated Event classes and model observers.
     */
    public function handle(object $event): void
    {
        $booking = $event->booking;

        $tokens = User::where('role', 'admin')
            ->whereNotNull('expo_token')
            ->pluck('expo_token')
            ->map(fn($t) => (string) $t)
            ->filter()
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        $clientName = optional($booking->client?->user)->name ?? 'A client';

        $this->expo->send(
            $tokens,
            '📋 New Booking',
            "{$clientName} made a new booking.",
            ['type' => 'new_booking', 'booking_id' => $booking->id]
        );
    }
}
