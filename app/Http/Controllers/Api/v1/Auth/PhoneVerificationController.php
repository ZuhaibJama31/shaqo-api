<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\v1\Controller;
use App\Rules\SomaliPhone;
use App\Services\DiditPhoneVerificationService;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    public function __construct(protected DiditPhoneVerificationService $didit) {}

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (preg_match('/^(61|62|63|64|65|66|68|69|71|77|90)\d{7}$/', $phone)) {
            $phone = '+252' . $phone;
        }

        return $phone;
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'phone_number' => ['required', new SomaliPhone],
            'channel' => 'sometimes|in:whatsapp,sms,telegram,voice',
            'locale' => 'sometimes|string|max:10',
        ]);

        $vendorData = $request->user()?->id ? (string) $request->user()->id : '';

        $response = $this->didit->sendCode(
            phoneNumber: $this->normalizePhone($data['phone_number']),
            vendorData: $vendorData,
            preferredChannel: $data['channel'] ?? 'whatsapp',
            locale: $data['locale'] ?? 'en-US',
        );

        if (!empty($response['error'])) {
            return response()->json([
                'message' => $response['message'],
                'details' => $response['details'] ?? null,
            ], $response['status'] ?? 500);
        }

        if (($response['status'] ?? null) === 'Blocked') {
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
            'phone_number' => ['required', new SomaliPhone],
            'code' => 'required|string|size:6',
        ]);

        $response = $this->didit->checkCode(
            phoneNumber: $this->normalizePhone($data['phone_number']),
            code: $data['code'],
        );

        if (!empty($response['error'])) {
            return response()->json([
                'message' => $response['message'],
                'details' => $response['details'] ?? null,
            ], $response['status'] ?? 500);
        }

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
