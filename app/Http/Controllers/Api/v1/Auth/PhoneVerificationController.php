<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\v1\Controller;
use App\Rules\E164Phone;
use App\Services\DiditPhoneVerificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    public function __construct(protected DiditPhoneVerificationService $didit) {}

    public function send(Request $request)
    {
        $data = $request->validate([
            'phone_number' => ['required', new E164Phone],
            'channel' => 'sometimes|in:whatsapp,sms,telegram,voice',
            'locale' => 'sometimes|string|max:10',
        ]);

        $vendorData = $request->user()?->id ? (string) $request->user()->id : '';

        $response = $this->didit->sendCode(
            phoneNumber: $data['phone_number'],
            vendorData: $vendorData,
            preferredChannel: $data['channel'] ?? 'whatsapp',
            locale: $data['locale'] ?? 'en-US',
        );

        if (isset($response['status']) && $response['status'] === 'Blocked') {
            return response()->json([
                'message' => 'Phone number blocked.',
                'reason' => $response['reason'] ?? 'spam',
            ], 403);
        }

        return response()->json([
            'message' => 'Verification code sent.',
            'status' => $response['status'] ?? 'sent',
            'vendor_data' => $vendorData,
        ]);
    }

    public function check(Request $request)
    {
        $data = $request->validate([
            'phone_number' => ['required', new E164Phone],
            'code' => 'required|string|size:6',
        ]);

        $response = $this->didit->checkCode(
            phoneNumber: $data['phone_number'],
            code: $data['code'],
        );

        $status = $response['status'] ?? null;

        $messages = [
            'Approved' => 'Phone number verified successfully.',
            'Declined' => 'Incorrect code. No attempts remaining. Please request a new code.',
            'Failed' => 'Incorrect code. Please try again.',
            'Expired or Not Found' => 'Code has expired. Please request a new one.',
        ];

        if ($status === 'Approved') {
            if ($request->user()) {
                $request->user()->forceFill([
                    'phone_verified_at' => now(),
                ])->save();
            }

            return response()->json([
                'message' => $messages['Approved'],
                'status' => $status,
                'phone' => $response['phone'] ?? null,
            ]);
        }

        if ($status === 'Declined') {
            return response()->json([
                'message' => $messages['Declined'],
                'status' => $status,
            ], 403);
        }

        if ($status === 'Failed') {
            return response()->json([
                'message' => $messages['Failed'],
                'status' => $status,
                'attempts_remaining' => $response['attempts_remaining'] ?? null,
            ], 400);
        }

        if ($status === 'Expired or Not Found') {
            return response()->json([
                'message' => $messages['Expired or Not Found'],
                'status' => $status,
            ], 410);
        }

        return response()->json($response);
    }
}
