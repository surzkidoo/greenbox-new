<?php

namespace App\Http\Controllers;

use App\Models\notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    // Create a new notification
    public function create(Request $request)
    {
        $rules = [
            'data' => 'required|data'
        ];

         // Create the validator instance
         $validator = Validator::make($request->all(), $rules);

         if ($validator->fails()) {
             // Customize the error response for API requests
             return response()->json([
                 'status' => 'error',
                 'message' => 'Validation failed',
                 'errors' => $validator->errors(),
             ], 422);
         }

         $validated = $validator->validated();

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
