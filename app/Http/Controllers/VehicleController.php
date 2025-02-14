<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    // Store a new vehicle
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
            'plate_number' => 'required|string|max:20|unique:vehicles',
            'capacity' => 'required|string',
            'location' => 'required|string',
            'delivery_type' => 'required|string',
            'model' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'driver_id' => 'nullable|exists:drivers,id',
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


        $vehicleData = $request->only(['name', 'plate_number', 'location', 'capacity', 'model','delivery_type', 'driver_id']);
        $vehicleData['user_id'] = Auth::id();


        if ($request->hasFile('image')) {
            $vehicleData['image'] = $request->file('image')->store('vehicle_images', 'public');
        }

        $vehicle = Vehicle::create($vehicleData);

        return response()->json(['status' => 'success', 'vehicle' => $vehicle], 201);
    }

    // Show a vehicle
    public function show($id): JsonResponse
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle not found.'], 404);
        }

        return response()->json(['status' => 'success', 'vehicle' => $vehicle], 200);
    }

    // Update a vehicle
    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'name' => 'sometimes|string|max:255',
            'plate_number' => 'sometimes|string|max:20|unique:vehicles',
            'capacity' => 'sometimes|string',
            'location' => 'sometimes|string',
            'delivery_type' => 'sometimes|string',
            'model' => 'sometimes|string|max:255',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'driver_id' => 'nullable|exists:drivers,id'
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


        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle not found.'], 404);
        }

        $vehicle->update($request->only(['name', 'plate_number', 'location', 'capacity', 'model','delivery_type', 'driver_id']));

        if ($request->hasFile('image')) {
            // Delete the old image
            if ($vehicle->image) {
                Storage::disk('public')->delete($vehicle->image);
            }
            $vehicle->image = $request->file('image')->store('vehicle_images', 'public');
            $vehicle->save();
        }

        return response()->json(['status' => 'success', 'vehicle' => $vehicle], 200);
    }

    // Delete a vehicle
    public function destroy($id): JsonResponse
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle not found.'], 404);
        }

        // Delete the image if it exists
        if ($vehicle->image) {
            Storage::disk('public')->delete($vehicle->image);
        }

        $vehicle->delete();

        return response()->json(['status' => 'success', 'message' => 'Vehicle deleted successfully.'], 200);
    }

    // List all vehicles for the authenticated user
    public function index($userId): JsonResponse
    {
        $vehicles = Vehicle::where('user_id', $userId)->get();

        return response()->json(['status' => 'success', 'vehicles' => $vehicles], 200);
    }
}
