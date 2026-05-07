<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Api\v1\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET api/v1/admin/notifications
     * All notifications, unread first
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get()
            ->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'type'       => $n->data['type']         ?? null,
                    'title'      => $n->data['title']        ?? null,
                    'message'    => $n->data['message']       ?? null,
                    'client_id'  => $n->data['client_id']    ?? null,
                    'client_name'=> $n->data['client_name']  ?? null,

                    // ← this is what admin uses to call the client
                    'client_phone' => $n->data['client_phone'] ?? null,

                    'booking_id' => $n->data['booking_id']   ?? null,
                    'read'       => !is_null($n->read_at),
                    'created_at' => $n->created_at->toDateTimeString(),
                ];
            });

        return response()->json($notifications);
    }

    /**
     * GET api/v1/admin/notifications/unread-count
     * Badge count for the bell icon
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * POST api/v1/admin/notifications/{id}/read
     * Mark one as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * POST api/v1/admin/notifications/read-all
     * Mark all as read (admin clears the bell)
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }
}