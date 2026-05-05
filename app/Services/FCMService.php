<?php 

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;

class FCMService
{
    protected function getAccessToken()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/firebase.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();

        return $token['access_token'];
    }

    public function send($tokens, $title, $body, $data = [])
    {
        $accessToken = $this->getAccessToken();

        $projectId = json_decode(
            file_get_contents(storage_path('app/firebase.json')),
            true
        )['project_id'];

        foreach ($tokens as $token) {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ]
            ]);
        }
    }
}