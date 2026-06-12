<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class DiditPhoneVerificationService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('didit.api_key');
        $this->baseUrl = config('didit.base_url');
    }

    private function sendRequest(string $method, string $path, array $data): Response
    {
        return Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->$method("{$this->baseUrl}/{$path}", $data);
    }

    public function sendCode(
        string $phoneNumber,
        string $vendorData = '',
        int $codeSize = 6,
        string $preferredChannel = 'whatsapp',
        string $locale = 'en-US'
    ): array {
        $response = $this->sendRequest('post', 'phone/send/', [
            'phone_number' => $phoneNumber,
            'options' => [
                'code_size' => $codeSize,
                'preferred_channel' => $preferredChannel,
                'locale' => $locale,
            ],
            'vendor_data' => $vendorData,
        ]);

        return $response->json();
    }

    public function checkCode(
        string $phoneNumber,
        string $code,
        string $voipNumberAction = 'DECLINE'
    ): array {
        $response = $this->sendRequest('post', 'phone/check/', [
            'phone_number' => $phoneNumber,
            'code' => $code,
            'voip_number_action' => $voipNumberAction,
        ]);

        return $response->json();
    }
}
