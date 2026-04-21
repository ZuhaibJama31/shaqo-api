<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * List bookings for the current user
     * GET /api/bookings
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'client') {
            $bookings = Booking::with(['worker.user', 'worker.category'])
                ->where('client_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $worker   = $user->worker;

            if (!$worker) {
                return response()->json([]);
            }

            $bookings = Booking::with(['client'])
                ->where('worker_id', $worker->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json($bookings);
    }

    /**
     * Create a new booking (clients only)
     * POST /api/bookings
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client') {
            return response()->json(['message' => 'Only clients can create bookings'], 403);
        }

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
            'message' => 'Booking request sent successfully',
            'booking' => $booking->load(['worker.user', 'client']),
        ], 201);
    }

    /**
     * Show a single booking
     * GET /api/bookings/{id}
     */
    public function show($id)
    {
        $booking = Booking::with(['worker.user', 'worker.category', 'client'])
            ->findOrFail($id);

        return response()->json($booking);
    }

    /**
     * Update booking status
     * PUT /api/bookings/{id}
     * Workers can: accept, reject
     * Clients can: cancel
     * Both can: completed
     */
    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $user    = $request->user();

        $data = $request->validate([
            'status'       => 'required|in:accepted,rejected,completed,cancelled',
            'agreed_price' => 'nullable|numeric|min:0',
        ]);

        // Only the assigned worker can accept or reject
        if (in_array($data['status'], ['accepted', 'rejected'])) {
            if ($user->role !== 'worker' || !$user->worker || $user->worker->id !== $booking->worker_id) {
                return response()->json(['message' => 'Only the assigned worker can accept or reject'], 403);
            }
        }

        // Only the client can cancel
        if ($data['status'] === 'cancelled') {
            if ($booking->client_id !== $user->id) {
                return response()->json(['message' => 'Only the client can cancel this booking'], 403);
            }
        }

        $booking->update($data);

        return response()->json([
            'message' => 'Booking updated',
            'booking' => $booking->fresh(['worker.user', 'client']),
        ]);
    }
}
