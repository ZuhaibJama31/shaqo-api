<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoNotificationService
{
    protected string $expoEndpoint = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send a push notification to one or more Expo push tokens.
     *
     * @param  string|array  $tokens   ExponentPushToken[xxx] strings
     * @param  string        $title
     * @param  string        $body
     * @param  array         $data     Extra payload sent to the app
     */
    public function send(string|array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = (array) $tokens;

        // Filter out empty / null tokens
        $tokens = array_filter($tokens, fn($t) => !empty($t));

        if (empty($tokens)) {
            Log::warning('ExpoNotification: no valid tokens provided');
            return;
        }

        $messages = array_map(fn($token) => [
            'to'    => $token,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
            'sound' => 'default',
        ], array_values($tokens));

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->expoEndpoint, $messages);

            if (!$response->successful()) {
                Log::error('ExpoNotification: HTTP error', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);
                return;
            }

            // Log individual ticket errors (Expo returns per-message receipts)
            $tickets = $response->json('data') ?? [];
            foreach ($tickets as $i => $ticket) {
                if (($ticket['status'] ?? '') === 'error') {
                    Log::error('ExpoNotification: ticket error', [
                        'token'   => $tokens[$i] ?? 'unknown',
                        'details' => $ticket,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('ExpoNotification: exception', ['message' => $e->getMessage()]);
        }
    }
}
