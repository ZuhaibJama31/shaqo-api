<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Api\v1\Controller;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Services\FCMService;
use App\Notifications\BookingStatusUpdatedNotification;

class AdminBookingController extends Controller
{
    /**
     * Admin: List all bookings
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bookings = Booking::with([
            'client',
            'worker.user',
            'worker.category'
        ])
        ->latest()
        ->get();

        return response()->json($bookings);
    }

    /**
     * Admin: Create booking
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'client_id'    => 'required|exists:users,id',
            'worker_id'    => 'required|exists:workers,id',
            'description'  => 'required|string|max:1000',
            'address'      => 'required|string|max:255',
            'city'         => 'required|string|max:100',
            'scheduled_at' => 'required|date',
            'status'       => 'required|in:pending,accepted,rejected,completed,cancelled',
            'agreed_price' => 'nullable|numeric|min:0',
        ]);

        $booking = Booking::create($data);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking->load([
                'client',
                'worker.user',
                'worker.category'
            ])
        ], 201);
    }

    /**
     * Admin: Show one booking
     */
    public function show(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking = Booking::with([
            'client',
            'worker.user',
            'worker.category'
        ])->findOrFail($id);

        return response()->json($booking);
    }

    public function update(Request $request, $id, FCMService $fcm)
{
    if ($request->user()->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $booking = Booking::findOrFail($id);

    $data = $request->validate([
        'client_id'    => 'sometimes|exists:users,id',
        'worker_id'    => 'sometimes|exists:workers,id',
        'description'  => 'sometimes|string|max:1000',
        'address'      => 'sometimes|string|max:255',
        'city'         => 'sometimes|string|max:100',
        'scheduled_at' => 'sometimes|date',
        'status'       => 'sometimes|in:pending,accepted,rejected,completed,cancelled',
        'agreed_price' => 'nullable|numeric|min:0',
    ]);

    // 1. Update booking
    $booking->update($data);

    // 2. Notify CLIENT
    $client = $booking->client;

    if ($client) {

        // ✅ Save DB notification
        $client->notify(new BookingStatusUpdatedNotification($booking));

        // ✅ Send PUSH
        $tokens = $client->deviceTokens->pluck('token')->toArray();

        if (!empty($tokens)) {
            $fcm->send(
                $tokens,
                'Booking Update',
                "Your booking is now {$booking->status}",
                ['booking_id' => $booking->id]
            );
        }
    }

    return response()->json([
        'message' => 'Booking updated successfully',
        'booking' => $booking->fresh([
            'client',
            'worker.user',
            'worker.category'
        ])
    ]);
}

    
    
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }
}