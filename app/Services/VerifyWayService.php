<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VerifyWayService
{
    public function sendOtp($phone, $code)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . env('VERIFYWAY_API_KEY'),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post('https://api.verifyway.com/api/v1/', [
            'recipient' => $phone,
            'type'      => 'otp',
            'code'      => $code,
            'channel'   => 'whatsapp',
            'fallback'  => 'yes',
            'lang'      => 'en',
        ])->json();
    }
}