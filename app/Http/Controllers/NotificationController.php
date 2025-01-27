<?php

namespace App\Http\Controllers;

use App\Models\notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Create a new notification
    public function create(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|data'
        ]);

        $notification = notification::create([
            'user_id' => Auth::id(),
            'data' => $validated['data'],
        ]);

        return response()->json(['status' => 'success', 'data' => $notification], 201);
    }

    // Get all notifications for the authenticated user
    public function index()
    {
        $notifications = notification::where('user_id', Auth::id())->get();
        return response()->json(['status' => 'success', 'data' => $notifications], 200);
    }

    // Mark a notification as read
    public function markAsRead($id)
    {
        $notification = notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['status' => 'success', 'message' => 'Notification marked as read.'], 200);
    }

    // Delete a notification
    public function destroy($id)
    {
        $notification = notification::findOrFail($id);
        $notification->delete();

        return response()->json(['status' => 'success', 'message' => 'Notification deleted successfully.'], 200);
    }
}
