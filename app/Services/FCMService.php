<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected function getAccessToken()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/firebase.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();

        return $token['access_token'] ?? null;
    }

    public function send($tokens, $title, $body, $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                Log::error('FCM: Failed to get access token');
                return false;
            }

            $firebaseConfig = json_decode(
                file_get_contents(storage_path('app/firebase.json')),
                true
            );

            $projectId = $firebaseConfig['project_id'] ?? null;

            if (!$projectId) {
                Log::error('FCM: Missing project_id');
                return false;
            }

            foreach ($tokens as $token) {

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => $data,
                        ]
                    ]
                );

                // 🔥 IMPORTANT: log response
                if (!$response->successful()) {
                    Log::error('FCM Send Failed', [
                        'token' => $token,
                        'response' => $response->body(),
                    ]);
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('FCM Exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}