<?php

namespace App\Http\Controllers;
use App\Models\address;
use App\Models\setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    // Create a new address
    public function store(Request $request)
    {
        $rules = [
            'phone' => 'nullable|string|max:15',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'address' => 'required|string',
            'street_address' => 'nullable|string',
            'city' => 'required|string',
            'lga' => 'required|string',
            'state' => 'required|string',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'required|string',
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


        $validated['user_id'] = Auth::id(); // Assign logged-in user's ID

        $address = address::create($validated);

        return response()->json(['status' => 'success', 'data' => $address, 'message' => 'Address created successfully.']);
    }

    // Read all addresses
    public function index()
    {
        $addresses = address::where('user_id', Auth::id())->get();
        return response()->json(['status' => 'success', 'data' => $addresses]);
    }

    // Read a specific address
    public function show()
    {

        $address = address::where('user_id', Auth::id())->get();
        return response()->json(['status' => 'success', 'data' => $address]);
    }

    // Update an existing address
    public function update(Request $request, $id)
    {
        $address = address::where('user_id', Auth::id())->findOrFail($id);

        $rules =[
            'phone' => 'nullable|string|max:15',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'street_address' => 'nullable|string',
            'city' => 'nullable|string',
            'lga' => 'nullable|string',
            'state' => 'nullable|string',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'nullable|string',
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


        $address->update($validated);

        return response()->json(['status' => 'success', 'data' => $address, 'message' => 'Address updated successfully.']);
    }

    // Delete an address
    public function destroy($id)
    {
        $address = address::where('user_id', Auth::id())->findOrFail($id);
        $address->delete();

        return response()->json(['status' => 'success', 'message' => 'Address deleted successfully.']);
    }
}

