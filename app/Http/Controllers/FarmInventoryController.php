<?php

namespace App\Http\Controllers;

use App\Models\farms;
use Illuminate\Http\Request;
use App\Models\farmInventory;
use Illuminate\Support\Facades\Validator;

class FarmInventoryController extends Controller
{


    public function getInventoryByFarmId($farmId)
    {
        $inventory = FarmInventory::where('farm_id', $farmId)->get();

        if ($inventory->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No inventory records found for this farm ID.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $inventory
        ], 200);
    }

    /**
     * Add to a specific inventory field.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addInventory(Request $request, $farmId)
    {
        $rules = [
            'counter' => 'required|integer',
            'type' => 'required|in:sold,death,purchase,birth',
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


        $inventory = farmInventory::where('farm_id', $farmId)->first();

        // Use the type to determine which field to increment by the counter amount
        $inventory->increment($request->type, $request->input('counter'));

        $farm = farms::where('id', $farmId)->first();
        $farm->increment('counter', $request->input('counter') + $farm->counter);


        return response()->json([
            'status' => 'success',
            'message' => 'Inventory updated successfully.',
            'inventory' => $inventory,
            'counter'=> $farm->counter,
        ]);
    }

    /**
     * Subtract from a specific inventory field.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function subtractInventory(Request $request, $farmId)
    {
        $rules =[
            'counter' => 'required|integer',
            'type' => 'required|in:sold,death,purchase,birth',
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


        $inventory = FarmInventory::where('farm_id', $farmId)->first();

        // Use the type to determine which field to decrement by the counter amount
        $inventory->decrement($request->type, $request->input('counter'));
        $farm = farms::where('id', $farmId)->first();
        $farm->decrement('counter', $request->input('counter'));


        return response()->json([
            'status' => 'success',
            'message' => 'Inventory updated successfully.',
            'inventory' => $inventory,
            'counter'=> $farm->counter,
        ]);
    }
}
