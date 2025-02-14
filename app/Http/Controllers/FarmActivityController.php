<?php
// app/Http/Controllers/ProductController.php
namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\farmActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FarmActivityController extends Controller
{
    // Display a listing of products
    public function index()
    {
        $farmActs = farmActivity::all();
        return response()->json($farmActs);
    }


    public function getActivityByFarmType($id)
    {
        $farmActs = farmActivity::where('farm_type_id',$id)->get();

        return response()->json($farmActs);
    }

    // Store a newly created product
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'period' => 'required|integer|max:2048',
            'vendor' => 'required|string',
            'detail' => 'required|string',
            'step' => 'required|integer',
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


        //check for last step
        $farmAct = farmActivity::where('step',$request->step)->where('farm_type_id',$validated['farm_type_id'])->first();
        if($farmAct && $request->step==$farmAct->step){
            return response()->json(['status'=>'error','message' => 'Step Already Exist'], 401);
        }


        $farm_type = farmActivity::create($validated);

        return response()->json(['message' => 'farm_type created successfully', 'farmType' => $farm_type], 201);
    }

    // Show a single product
    public function show($id)
    {
        $farmAct = farmActivity::findOrFail($id);
        return response()->json($farmAct);
    }

    // Update an existing product
    public function update(Request $request, $id)
    {
        $farmAct = farmActivity::findOrFail($id);

        $rules = [
            'name' => 'string|max:255',
            'period' => 'integer|max:2048',
            'vendor' => 'string',
            'detail' => 'string',
            'farm_type_id' => 'integer',
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


        $farmAct->update($validated);

        return response()->json(['message' => 'Farm Activity updated successfully', 'data' => $farmAct]);
    }

    // Delete a product
    public function destroy($id)
    {
        $farmAct = farmActivity::findOrFail($id);
        $farmAct->delete();

        return response()->json(['message' => 'FarmActivity deleted successfully']);
    }




}
