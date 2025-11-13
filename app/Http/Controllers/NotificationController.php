<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Display all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user(); // Retrieve logged-in user (via Sanctum)

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notifications = Notification::where('web_user_id', $user->web_user_id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get(['notification_id', 'message', 'is_read', 'created_at']);

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Helper for creating a new notification.
     */
    public static function createNotification($web_user_id, $message)
    {
        Notification::create([
            'notification_id' => \Illuminate\Support\Str::uuid(),
            'web_user_id' => $web_user_id,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}

