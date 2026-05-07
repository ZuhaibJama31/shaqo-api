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
    
    // Create booking (your existing code)
    $booking = Booking::create([
        'client_id' => $user->id,
        'worker_id' => $request->worker_id,
        'description' => $request->description,
        'address' => $request->address,
        'city' => $request->city,
        'scheduled_at' => $request->scheduled_at,
        'status' => 'pending',
    ]);
    
    // Find admin
    $admin = User::where('role', 'admin')->first();
    
    // Send notification if admin has token
    if ($admin && $admin->expo_token) {
        $this->sendExpoNotification($admin->expo_token, $user->name, $booking->city);
    }
    
    return response()->json(['message' => 'Booking created'], 201);
}

// Add this function to your controller
private function sendExpoNotification($token, $clientName, $city)
{
    $ch = curl_init('https://exp.host/--/api/v2/push/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'to' => $token,
        'title' => 'New Booking!',
        'body' => "$clientName booked a service in $city",
        'sound' => 'default'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

}