<?php

namespace App\Http\Controllers;

use App\Models\LogMessage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LogMessageController extends Controller
{
    public function index(Request $request)
{
    $query = LogMessage::query();

    if ($request->has('type')) {
        $query->where('type', $request->input('type'));
    }

    $logMessages = $query->get();

    return response()->json(['status'=>'success',
                            'data'=>$logMessages]);
}
}
