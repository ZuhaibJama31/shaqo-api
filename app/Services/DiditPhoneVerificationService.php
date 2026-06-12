<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class DiditPhoneVerificationService
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('didit.api_key');
        $this->baseUrl = config('didit.base_url', 'https://verification.didit.me/v3');
    }

    public function sendCode(
        string $phoneNumber,
        string $vendorData = '',
        int $codeSize = 6,
        string $preferredChannel = 'whatsapp',
        string $locale = 'en-US'
    ): array {
        return $this->call('phone/send/', [
            'phone_number' => $phoneNumber,
            'options' => [
                'code_size' => $codeSize,
                'preferred_channel' => $preferredChannel,
                'locale' => $locale,
            ],
            'vendor_data' => $vendorData,
        ]);
    }

    public function checkCode(
        string $phoneNumber,
        string $code,
        string $voipNumberAction = 'DECLINE'
    ): array {
        return $this->call('phone/check/', [
            'phone_number' => $phoneNumber,
            'code' => $code,
            'voip_number_action' => $voipNumberAction,
        ]);
    }

    private function call(string $path, array $data): array
    {
        if (!$this->apiKey) {
            return [
                'error' => true,
                'status' => 500,
                'message' => 'DIDIT_API_KEY is not configured.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/{$path}", $data);

            if ($response->failed()) {
                return [
                    'error' => true,
                    'status' => $response->status(),
                    'message' => 'Didit API request failed.',
                    'details' => $response->json(),
                ];
            }

            return $response->json() ?: [];
        } catch (RequestException $e) {
            return [
                'error' => true,
                'status' => $e->response?->status() ?? 500,
                'message' => 'Didit API returned an error.',
                'details' => $e->response?->json(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'status' => 500,
                'message' => 'Could not reach Didit API: ' . $e->getMessage(),
            ];
        }
    }
}
