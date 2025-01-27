<?php
namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    // Create a new driver
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|string|email|unique:drivers|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'liecense' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'permit' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        $driverData = $request->only(['name', 'phone', 'email']);
        $driverData['user_id'] = Auth::id(); // Assign the authenticated user ID


        // Handle image upload
        if ($request->hasFile('image')) {
            $driverData['image'] = $request->file('image')->store('drivers', 'public');
        }

        if ($request->hasFile('liecense')) {
            $driverData['liecense'] = $request->file('liecense')->store('drivers', 'public');
        }

        if ($request->hasFile('permit')) {
            $driverData['permit'] = $request->file('permit')->store('drivers', 'public');
        }


        $driver = Driver::create($driverData);


        if($request->vehicle_id){
            $vh = Vehicle::where('id',$request->vehicle);
            $vh->driver_id = $driver->id;
            $vh->save();
        }

        return response()->json(['status' => 'success', 'driver' => $driver], 201);
    }

    // Read a driver's information
    public function show($id): JsonResponse
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver not found.'], 404);
        }

        return response()->json(['status' => 'success', 'driver' => $driver], 200);
    }

    public function logisticDrivers($id): JsonResponse
    {
        $drivers = Driver::where('user_id',$id)->get();

        if (!$drivers) {
            return response()->json(['status' => 'error', 'message' => 'Drivers not found.'], 404);
        }

        return response()->json(['status' => 'success', 'driver' => $drivers], 200);
    }

    // Update a driver's information
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
              'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'liecense' => 'nullable|mimes:pdf|max:2048', // 2MB max
            'permit' => 'nullable|mimes:pdf|max:2048', // 2MB max
        ]);

        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver not found.'], 404);
        }

        $driverData = $request->only(['name', 'phone', 'email']);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($driver->image) {
                Storage::disk('public')->delete($driver->image);
            }
            $driverData['image'] = $request->file('image')->store('drivers', 'public');
        }

        $driver->update($driverData);

        if($request->vehicle_id){
            $vh = Vehicle::where('id',$request->vehicle_id)->first();
            if(!$vh){
                return response()->json(['status' => 'error', 'message' => "Driver not Found"], 404);

            }
            $vh->driver_id = $driver->id;
            $vh->save();
        }

        return response()->json(['status' => 'success', 'driver' => $driver], 200);
    }

    // Delete a driver
    public function destroy($id): JsonResponse
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver not found.'], 404);
        }

        // Delete the image from storage if it exists
        if ($driver->image) {
            Storage::disk('public')->delete($driver->image);
        }

        $driver->delete();

        return response()->json(['status' => 'success', 'message' => 'Driver deleted successfully.'], 200);
    }

    // List all drivers for the authenticated user
    public function index(): JsonResponse
    {
        $drivers = Driver::where('user_id', Auth::id())->get();

        return response()->json(['status' => 'success', 'drivers' => $drivers], 200);
    }


    public function verifyDriver($id): JsonResponse
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver not found.'], 404);
        }

        $driver->verified = true;
        $driver->save();

        return response()->json(['status' => 'success', 'message' => 'Driver verified successfully.', 'driver' => $driver], 200);
    }

    // Assign a vehicle to a driver
    public function assignVehicle(Request $request, $driverId): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $driver = Driver::find($driverId);
        $vehicle = Vehicle::find($request->vehicle_id);

        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver not found.'], 404);
        }

        if (!$vehicle) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle not found.'], 404);
        }

        if ($vehicle->driver_id !== null) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle is already assigned to another driver.'], 400);
        }

        // Assign the vehicle to the driver
        $vehicle->driver_id = $driver->id;
        $vehicle->save();

        return response()->json(['status' => 'success', 'message' => 'Vehicle assigned to driver successfully.', 'vehicle' => $vehicle], 200);
    }

}
