<?php

namespace App\Http\Controllers\Api\v1\Client;

use App\Http\Controllers\Api\v1\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FCMService;
use App\Notifications\BookingCreatedNotification;

class ClientBookingController extends Controller
{
    public function store(Request $request, FCMService $fcm)
    {
        $user = $request->user();

        $data = $request->validate([
            'worker_id'    => 'required|exists:workers,id',
            'description'  => 'required|string|max:1000',
            'address'      => 'required|string|max:255',
            'city'         => 'required|string|max:100',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $booking = DB::transaction(function () use ($user, $data, $fcm) {

            // 1. Create booking
            $booking = Booking::create([
                'client_id'    => $user->id,
                'worker_id'    => $data['worker_id'],
                'description'  => $data['description'],
                'address'      => $data['address'],
                'city'         => $data['city'],
                'scheduled_at' => $data['scheduled_at'],
                'status'       => 'pending',
            ]);

            $booking->load('client');

            // 2. Get the single admin
            $admin = User::where('role', 'admin')->with('deviceTokens')->first();

            if (!$admin) {
                return $booking; // no admin found, skip notifications
            }

            // 3. DB notification
            $admin->notify(new BookingCreatedNotification($booking));
            

            // 4. FCM push
            $tokens = $admin->deviceTokens->pluck('token')->toArray();

            if (!empty($tokens)) {
                $fcm->send(
                    $tokens,
                    'New Booking',
                    "{$user->name} made a booking — {$booking->city}",
                    [
                        'type'       => 'new_booking',
                        'booking_id' => (string) $booking->id,
                        'client_id'  => (string) $user->id,
                    ]
                );
            }

            return $booking;
        });

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking->load(['worker.user', 'client']),
        ], 201);
    }
}