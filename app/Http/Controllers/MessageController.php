<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\message;
use App\Models\notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function getActiveContacts()
    {
        $userId = Auth::id();

        // Retrieve contacts with their last message
        $contacts = Message::select('id', 'sender_id', 'receiver_id', 'message', 'created_at')
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->orderBy('created_at', 'desc') // Order by the most recent message
            ->get()
            ->groupBy(function ($message) use ($userId) {
                // Group by the other participant in the conversation
                return $message->sender_id === $userId ? $message->receiver_id : $message->sender_id;
            })
            ->map(function ($group) {
                // Get the most recent message for each group
                return $group->first();
            });

        // Map to format response with contact info and last message
        $formattedContacts = $contacts->map(function ($lastMessage) use ($userId) {
            $contactId = $lastMessage->sender_id === $userId
                ? $lastMessage->receiver_id
                : $lastMessage->sender_id;

            $contact = User::select('id', 'firstname','lastname', 'avatar', 'email') // Replace with relevant columns
                ->find($contactId);

            return [
                'contact' => $contact,
                'last_message' => $lastMessage->message,
                'last_message_time' => $lastMessage->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'contacts' => $formattedContacts->values(), // Reset keys to be sequential
        ]);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $message = message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);

        $notification = notification::create([
            'user_id' =>$validated['receiver_id'],
            'data' => $message->message,
        ]);

        $notification->save();

        // Optionally broadcast the message here or handle via Socket.IO directly

        return response()->json(['status' => 'success', 'message' => $message], 201);
    }

    public function getMessages($userId)
    {
        $messages = message::where(function ($query) use ($userId) {
            $query->where('sender_id', Auth::id())
                ->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', Auth::id());
        })->get();

        return response()->json(['status' => 'success', 'messages' => $messages]);
    }


    public function searchContacts(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:255',
        ]);

        $query = $request->query('query');

        $userId = Auth::id();

        $contacts = User::where('id', '!=', $userId)
            ->where(function ($q) use ($query) {
                $q->where('firstname', 'LIKE', "%$query%")
                  ->orWhere('lastname', 'LIKE', "%$query%")
                  ->orWhere('email', 'LIKE', "%$query%");
            })
            ->select('id', 'firstname', 'lastname', 'avatar', 'email')
            ->get();

        return response()->json([
            'status' => 'success',
            'contacts' => $contacts,
        ]);
    }
}
