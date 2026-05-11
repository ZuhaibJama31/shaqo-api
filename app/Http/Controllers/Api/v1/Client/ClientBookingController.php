<?php

namespace App\Http\Controllers\Api\v1\Client;

use App\Http\Controllers\Api\v1\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Services\ExpoNotificationService;
use Illuminate\Http\Request;

class ClientBookingController extends Controller
{
    public function __construct(protected ExpoNotificationService $expo) {}

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

    public function store(Request $request)
    {
        $user = $request->user();

        $booking = Booking::create([
            'client_id'    => $user->id,
            'worker_id'    => $request->worker_id,
            'description'  => $request->description,
            'address'      => $request->address,
            'city'         => $request->city,
            'scheduled_at' => $request->scheduled_at,
            'status'       => 'pending',
        ]);

        // 🔔 Notify admin
        $adminTokens = User::where('role', 'admin')
            ->whereNotNull('expo_token')
            ->pluck('expo_token')
            ->map(fn($t) => (string) $t)
            ->all();

        if (!empty($adminTokens)) {
            $this->expo->send(
                $adminTokens,
                '📋 New Booking!',
                "{$user->name} booked a service in {$booking->city}",
                ['type' => 'new_booking', 'booking_id' => $booking->id]
            );
        }

        return response()->json([
            'message' => 'Booking created',
            'booking' => $booking,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::with(['worker.user', 'worker.category'])
            ->where('client_id', $user->id)
            ->findOrFail($id);

        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::where('client_id', $user->id)
            ->findOrFail($id);

        $booking->update($request->only([
            'description',
            'address',
            'city',
            'scheduled_at',
        ]));

        return response()->json([
            'message' => 'Booking updated',
            'booking' => $booking,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::where('client_id', $user->id)
            ->findOrFail($id);

        $booking->delete();

        return response()->json(['message' => 'Booking deleted']);
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,rejected,completed,cancelled',
        ]);

        $booking->update(['status' => $request->status]);
        $booking->refresh();

        $clientUser  = $booking->client ?? User::find($booking->client_id);
        $clientToken = $clientUser?->expo_token ? (string) $clientUser->expo_token : null;

        if ($clientToken) {
            $messages = [
                'pending'   => ['⏳ Booking Pending',  'Your booking is awaiting confirmation.'],
                'accepted'  => ['✅ Booking Accepted',  'Your booking has been accepted!'],
                'rejected'  => ['❌ Booking Rejected',  'Your booking was not accepted.'],
                'completed' => ['🎉 Booking Completed', 'Your booking is complete. Thank you!'],
                'cancelled' => ['🚫 Booking Cancelled', 'Your booking has been cancelled.'],
            ];

            [$title, $body] = $messages[$booking->status]
                ?? ['📌 Booking Updated', "Your booking status: {$booking->status}"];

            $this->expo->send(
                $clientToken,
                $title,
                $body,
                ['type' => 'booking_status', 'booking_id' => $booking->id, 'status' => $booking->status]
            );
        }

        return response()->json(['message' => 'Status updated', 'booking' => $booking]);
    }


    public function cancel(Request $request, $id)
{
    $user = $request->user();

    $booking = Booking::where('client_id', $user->id)
        ->whereIn('status', ['pending', 'accepted']) 
        ->findOrFail($id);

    $booking->update(['status' => 'cancelled']);

    // 🔔 Notify admin
    $adminTokens = User::where('role', 'admin')
        ->whereNotNull('expo_token')
        ->pluck('expo_token')
        ->map(fn($t) => (string) $t)
        ->all();

    if (!empty($adminTokens)) {
        $this->expo->send(
            $adminTokens,
            '🚫 Booking Cancelled',
            "{$user->name} cancelled their booking",
            ['type' => 'booking_cancelled', 'booking_id' => (string) $booking->id]
        );
    }

    return response()->json([
        'message' => 'Booking cancelled',
        'booking' => $booking,
    ]);
}
}