<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    // Get settings for the authenticated user
    public function index()
    {
        $settings = setting::where('user_id', Auth::id())->first();

        return response()->json(['status' => 'success', 'data' => $settings], 200);
    }

    // Create or update settings for the authenticated user
    public function store(Request $request)
    {
        $rules = [
            'two_factor_auth' => 'boolean',
            'live_location' => 'boolean',
            'team_link' => 'boolean',
            'weather' => 'boolean',
            'humidity' => 'boolean',
            'default_shipping' => 'string'
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


        // Check if settings already exist for the user
        $settings = setting::updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return response()->json(['status' => 'success', 'data' => $settings], 201);
    }

    // Get settings by user ID
    public function show($id)
    {
        $settings = setting::where('user_id', $id)->firstOrFail();

        return response()->json(['status' => 'success', 'data' => $settings], 200);
    }

    // Update specific settings
    public function update(Request $request, $id)
    {
        $rules = [
            'two_factor_auth' => 'boolean',
            'live_location' => 'boolean',
            'team_link' => 'boolean',
            'weather' => 'boolean',
            'humidity' => 'boolean',
            'default_shipping'=> 'string'
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

        $settings = setting::where('user_id', $id)->firstOrFail();
        $settings->update($validated);

        return response()->json(['status' => 'success', 'data' => $settings], 200);
    }

    // Delete settings for a user
    public function destroy($id)
    {
        $settings = setting::where('user_id', $id)->firstOrFail();
        $settings->delete();

        return response()->json(['status' => 'success', 'message' => 'Settings deleted successfully.'], 200);
    }


}
