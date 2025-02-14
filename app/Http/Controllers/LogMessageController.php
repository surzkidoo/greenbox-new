<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\LogMessage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LogMessageController extends Controller
{

    public function getLogDetails(Request $request)
    {
        // Retrieve query parameters for filtering.
        $email     = $request->input('email');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        // Start the query and eager load the related user (assuming 'name' holds the username).
        $query = LogMessage::with('user:id,firstname,lastname');

        // Filter by email if provided.
        if ($email) {
            $query->where('email', 'like', "%{$email}%");
        }

        // Filter by a date range (using created_at field).
        if ($startDate && $endDate) {
            // Parse dates to ensure valid Carbon instances.
            $start = Carbon::parse($startDate)->startOfDay();
            $end   = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        } elseif ($startDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $query->where('created_at', '>=', $start);
        } elseif ($endDate) {
            $end = Carbon::parse($endDate)->endOfDay();
            $query->where('created_at', '<=', $end);
        }

        // Retrieve the filtered log messages.
        $logs = $query->get();

        // Map the results to include only the desired fields.
        $result = $logs->map(function ($log) {
            return [
                "id" => $log->id,
                'email'     => $log->email,
                'full name'  => $log->user ? $log->user->fullname ." ". $log->user->lastname: null,
                'role'      => $log->role,
                'type'      => $log->type,
                'device_info'      => $log->device_info,
                'message'      => $log->message,
                'ip_address'      => $log->ip_address,
                'created_at'=> $log->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $result,
        ]);
    }
}


