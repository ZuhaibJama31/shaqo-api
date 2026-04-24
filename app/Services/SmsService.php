<?php 

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;

class SmsService
{
    public function sendOtp($phone, $message)
    {
        $at = new AfricasTalking(
            env('sandbox'),
            env('atsk_6659db0a34ef0925a5cc9c5deee5e940082baebd700eb32aaa51f29626cf688bce204957')
        );

        $sms = $at->sms();

        return $sms->send([
            'to' => $phone,
            'message' => $message,
            
        ]);
    }
}