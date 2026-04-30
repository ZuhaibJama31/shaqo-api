<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class ClientBookingController extends Controller
{
    /**
     * GET /api/client/bookings
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
     * POST /api/client/bookings
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'worker_id'    => 'required|exists:workers,id',
            'description'  => 'required|string|max:1000',
            'address'      => 'required|string|max:255',
            'city'         => 'required|string|max:100',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $booking = Booking::create([
            'client_id'    => $user->id,
            'worker_id'    => $data['worker_id'],
            'description'  => $data['description'],
            'address'      => $data['address'],
            'city'         => $data['city'],
            'scheduled_at' => $data['scheduled_at'],
            'status'       => 'pending',
        ]);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking->load(['worker.user', 'client']),
        ], 201);
    }

    /**
     * GET /api/client/bookings/{id}
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
     * DELETE /api/client/bookings/{id}
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