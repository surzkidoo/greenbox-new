<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\farmTask;
use App\Models\FarmType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BenefitController extends Controller
{
    // Display a listing of products
    public function index()
    {
        $benefits = Benefit::paginate(12);
        return response()->json($benefits);
    }

    // Store a newly created product
    public function store(Request $request)
    {
        $rules = [
            // 'farm_name' => 'required|string|max:255',
            'farm_produce' => 'required|string|max:2048',
            'working_cost' => 'required|string',
            'quantity_required' => 'required|string',
            'unit_price' => 'required|string',
            'measures' => 'required|string',
            'variable_cost' => 'required|string',
            'defect_liability' => 'required|string',
            'total_sales' => 'required|string',
            'fixed_assets' => 'required|string',
            'tax' => 'required|string',
            'gross_profit' => 'required|string',
            'net_profit' => 'required|string',
            'farm_type_id' => 'required|integer',
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



       $farmtype =   FarmType::where('id',$validated['farm_type_id'])->first();

       if(!$farmtype){
        return response()->json(['status'=>'error','message' => 'Failed To Create Benefit, Invalid FarmType ID'], 404);
       }

       $benefit = Benefit::create($validated);

        return response()->json(['status'=>'success','message' => 'Cost Benefit created successfully', 'farmType' => $benefit], 201);
    }

    // Show a single benefit
    public function show($id)
    {
        $benefit = Benefit::findOrFail($id);
        return response()->json($benefit);
    }

    // Update an existing product
    public function update(Request $request, $id)
    {
        $benefit = Benefit::findOrFail($id);

        $rules = [
            // 'farm_name' => 'string|max:255',
            'working_cost' => 'string',
            'quantity_required' => 'string',
            'unit_price' => 'string',
            'measures' => 'string',
            'variable_cost' => 'string',
            'defect_liability' => 'string',
            'total_sales' => 'string',
            'fixed_assets' => 'string',
            'tax' => 'string',
            'gross_profit' => 'string',
            'net_profit' => 'string',
            'farm_type_id' => 'integer',
            // 'farm_type' => 'integer',
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


        $farmtype =   FarmType::where('id',$validated['farm_type_id'])->first();

        if(!$farmtype){
         return response()->json(['status'=>'error','message' => 'Failed To Update Benefit, Invalid FarmType ID'], 404);
        }





        $benefit->update($validated);

        return response()->json(['message' => 'Cost Benefit updated successfully', 'data' => $benefit]);
    }

    // Delete a product
    public function destroy($id)
    {
        $benefit = Benefit::findOrFail($id);
        $benefit->delete();

        return response()->json(['message' => 'Benefit deleted successfully']);
    }
}
