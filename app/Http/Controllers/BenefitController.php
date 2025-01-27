<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\farmTask;
use App\Models\FarmType;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            // 'farm_name' => 'required|string|max:255',
            // 'farm_produce' => 'required|string|max:2048',
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
            'farm_type_id' => 'required|string',
            // 'farm_type' => 'required|integer',
        ]);


       $farmtype =   FarmType::where('id',$validated['farm_type_id'])->first();

       if(!$farmtype){
        return response()->json(['status'=>'error','message' => 'Failed To Create Benefit, Invalid FarmType ID'], 404);
       }


    //    $validated['farm_type'] = $farmtype->farm_type;
       $validated['farm_name'] = $farmtype->farm_name;
       $validated['farm_produce'] = $farmtype->farm_produce;


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

        $validated = $request->validate([
            // 'farm_name' => 'string|max:255',
            // 'farm_produce' => 'string|max:2048',
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
            'farm_type_id' => 'string',
            // 'farm_type' => 'integer',
        ]);

        $farmtype =   FarmType::where('id',$validated['farm_type_id'])->first();

        if(!$farmtype){
         return response()->json(['status'=>'error','message' => 'Failed To Update Benefit, Invalid FarmType ID'], 404);
        }


     //    $validated['farm_type'] = $farmtype->farm_type;
        $validated['farm_name'] = $farmtype->farm_name;
        $validated['farm_produce'] = $farmtype->farm_produce;



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
