<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ExpoNotificationService;
use Illuminate\Auth\Events\Registered;

class NotifyAdminOnClientRegistered
{
    public function __construct(protected ExpoNotificationService $expo) {}

    public function handle(Registered $event): void
    {
        $newUser = $event->user;

        // Only fire for clients
        if ($newUser->role !== 'client') {
            return;
        }

        // Collect all admin Expo tokens
        $tokens = User::where('role', 'admin')
            ->whereNotNull('expo_token')
            ->pluck('expo_token')
            ->map(fn($t) => (string) $t)   // cast ExpoPushToken VO → string
            ->filter()
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        $this->expo->send(
            $tokens,
            '👤 New Client Registered',
            "{$newUser->name} just signed up.",
            ['type' => 'new_client', 'user_id' => $newUser->id]
        );
    }
}
