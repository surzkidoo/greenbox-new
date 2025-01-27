<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\farms;
use App\Models\farmTask;
use App\Models\FarmType;
use App\Models\farmActivity;
use Illuminate\Http\Request;
use App\Models\farmInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FarmsController extends Controller
{
    // Display a listing of products
    public function index()

    {
        $farms = farms::with(['task.activity','type','user','inventory'])->where('status','activated')->paginate(20);
        $farmtype = FarmType::count();

        // Total number of tasks
        $totalTasks = DB::table('farm_tasks')->count();

        // Number of completed tasks
        $completedTasks = DB::table('farm_tasks')->where('status', 'completed')->count();

        // Calculate the completion rate
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        return response()->json(["status"=>"success",'data'=>['active_farms' => $farms , 'farm_types' => $farmtype, 'farm_completion_rate'=>$completionRate]]);
    }


    // Store a newly created product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_name' => 'required|string|max:255',
            'farm_capacity' => 'required|integer|max:2048',
            'budget' => 'required|integer',
            'counter' => 'integer',
            'farm_type_id' => 'required|string',

        ]);
        $validated['user_id'] = Auth::id();
        $farm = farms::create($validated);

        $farm->status = 'activated';
        $farm->save();

        $activities = FarmActivity::where('farm_type_id', $validated['farm_type_id'])
        ->orderBy('step')
        ->get();

        // Assign the fetched activities to the newly created farm
        foreach ($activities as $activity) {

            //$status = ($activity->step == 1) ? 'in progress' : 'not started';
            // $start_date = ($activity->step == 1) ? now() : null;


            farmTask::create([
                'farm_id' => $farm->id,
                'farm_activities_id' => $activity->id,
                 'status' =>  'not started',
            ]);
        }

        //Create Default Inventory

        farmInventory::create(['farm_id'=>$farm->id]);
        return response()->json(["status"=>"success",'message' => 'Farm created successfully', 'data' => $farm], 201);
    }

    // Show a single product
    public function show($id)
    {

        $farm = farms::with(['task.activity','type','user','inventory'])->findOrFail($id);

        $task = farmTask::where('farm_id',$farm->id)->where('status','in progress')->first();

        $result =  $task ? $this->calculateRemainingPercentage($task) : null;
        return response()->json(["status"=>"success","data"=>['farm'=>$farm,"progress"=>$result,'current_task'=>$task]]);
    }

        // Show a single product
        public function userFarms(Request $request, $id)
        {
            $typeId = $request->input('type_id'); // Get the type filter from the request

            // Query with optional type filter
            $query = farms::with(['task.activity', 'type', 'user', 'inventory'])
                ->where('status', 'active')
                ->where('user_id', $id);

            if (!empty($typeId)) {
                $query->where('type_id', $typeId);// Apply type filter
            }

            $crop = farms::where('user_id', $id)
            ->whereHas('type', function ($query) {
                $query->where('farm_type', 'crop');
            })
            ->count();

        // Count livestock
        $livestock = farms::where('user_id', $id)
            ->whereHas('type', function ($query) {
                $query->where('farm_type', 'livestock');
            })
            ->count();


            $farms = $query->paginate(20);

            $farmsCount = $query->count();

            return response()->json([
                'status'=>'success',
                'data'=>[
                    'active_farms' => $farms,
                    'livestock_count'=>$livestock,
                    'crop_count'=>$crop,
                    'active_count' => $farmsCount,
                ]

            ]);
        }


    // Update an existing product
    public function update(Request $request, $id)
    {
        $farm = farms::findOrFail($id);

        $validated = $request->validate([
            'farm_name' => 'string|max:255',
            'farm_capacity' => 'integer|max:2048',
            'budget' => 'integer',
            'counter' => 'integer',
            'farm_type_id' => 'string',
        ]);


        $farm->update($validated);

        return response()->json(['status'=>'error','message' => 'Farm updated successfully', 'data' => $farm]);
    }

    // Delete a product
    public function destroy($id)
    {
        $farm = farms::findOrFail($id);
        $farm->delete();
        return response()->json(['status'=>'success','message' => 'Farm deleted successfully']);
    }

    function calculateRemainingPercentage($task)
    {
        // Check if the task has started and has an end time
        if (!$task->start_date || !$task->expected_end_time) {
            return 0; // No period available
        }

        $startedAt = Carbon::parse($task->start_date);
        $expectedEndTime = Carbon::parse($task->expected_end_time);
        $now = Carbon::now();

        // Total period in days
        $totalperiod = $startedAt->diffInDays($expectedEndTime);

        // Remaining period in days
        $remainingDays = $now->lessThan($expectedEndTime) ? $now->diffInDays($expectedEndTime) : 0;

        // Calculate the remaining percentage
        $remainingPercentage = $totalperiod > 0 ? ($remainingDays / $totalperiod) * 100 : 0;

        return round($remainingPercentage, 2); // Round to 2 decimal places
    }

}
