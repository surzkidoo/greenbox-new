<?php

namespace App\Http\Controllers;

use App\Models\FarmType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FarmTypeController extends Controller
{
    // Display a listing of farm types
    public function index(Request $request)
    {

        $bytype = $request->input('farmtype');


        $query = FarmType::query();

        if(!empty($bytype)){
          $query->where('farm_type',$bytype);
        }

        $farmTypes = $query->paginate(12);

        return response()->json($farmTypes);
    }

    // Store a newly created farm type
    public function store(Request $request)
    {
        $rules = [
            'farm_name' => 'required|string|max:255',
            'farm_url' => 'required|image|max:2048',
            'farm_produce' => 'required|string',
            'farm_type' => 'required|in:crop,livestock',
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


        // Handle image upload
        if ($request->hasFile('farm_url')) {
            $imageName = time() . '.' . $request->file('farm_url')->getClientOriginalExtension();
            $request->file('farm_url')->move(public_path('images/icons'), $imageName);
            $validated['farm_url'] = $imageName; // Save the correct field
        }

        $farmType = FarmType::create($validated);

        return response()->json(['message' => 'Farm type created successfully', 'farmType' => $farmType], 201);
    }

    // Show a single farm type
    public function show($id)
    {
        $farmType = FarmType::findOrFail($id);
        return response()->json($farmType);
    }

    // Update an existing farm type
    public function update(Request $request, $id)
    {
        $farmType = FarmType::findOrFail($id);

        $rules = [
            'farm_name' => 'string|max:255',
            'farm_url' => 'image|max:2048',
            'farm_produce' => 'string',
            'farm_type' => 'in:crop,livestock',
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


        // Handle image upload
        if ($request->hasFile('farm_url')) {
            $imageName = time() . '.' . $request->file('farm_url')->getClientOriginalExtension();
            $request->file('farm_url')->move(public_path('images/icons'), $imageName);
            $validated['farm_url'] = $imageName; // Save the correct field
        }

        $farmType->update($validated);

        return response()->json(['message' => 'Farm type updated successfully', 'farmType' => $farmType]);
    }

    // Delete a farm type
    public function destroy($id)
    {
        $farmType = FarmType::findOrFail($id);
        $farmType->delete();

        return response()->json(['message' => 'Farm type deleted successfully']);
    }
}
