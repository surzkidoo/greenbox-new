<?php

namespace App\Http\Controllers;
use App\Models\address;
use App\Models\setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    // Create a new address
    public function store(Request $request)
    {
        $validated = $request->validate([
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
        ]);

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

        $validated = $request->validate([
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
        ]);

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

