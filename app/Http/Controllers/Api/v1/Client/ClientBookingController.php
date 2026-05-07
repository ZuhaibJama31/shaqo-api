<?php

namespace App\Http\Controllers\Api\v1\Client;

use App\Http\Controllers\Api\v1\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FCMService;
use App\Notifications\BookingCreatedNotification;


class ClientBookingController extends Controller
{
    /**
     * GET api/v1/client/bookings
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::with(['worker.user', 'worker.category'])
            ->where('client_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->values()
            ->map(function ($booking, $index) {
                $booking->client_booking_number = $index + 1;
                return $booking;
            });

        return response()->json($bookings);
    }

    /**
     * POST api/v1/client/bookings
     */
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

        /* 2. GET ADMINS (Eager load deviceTokens for performance)
        $admins = User::where('role', 'admin')->with('deviceTokens')->get();

        // 3. Notify Admins
        foreach ($admins as $admin) {
            
            $admin->notify(new BookingCreatedNotification($booking));
            

            // ✅ Send PUSH via FCM
            $tokens = $admin->deviceTokens->pluck('token')->toArray();

            if (!empty($tokens)) {
                $fcm->send(
                    $tokens,
                    'New Booking',
                    'A client created a new booking',
                    ['booking_id' => (string)$booking->id]
                );
            }
        }
            */

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking->load(['worker.user', 'client']),
        ], 201);
    }

    /**
     * GET api/v1/client/bookings/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::with(['worker.user', 'worker.category'])
            ->where('client_id', $user->id)
            ->findOrFail($id);

        return response()->json($booking);
    }

    /**
     * DELETE api/v1/client/bookings/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::where('client_id', $user->id)
            ->findOrFail($id);

        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }
}
