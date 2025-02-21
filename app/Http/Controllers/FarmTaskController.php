<?php
namespace App\Http\Controllers;

use DateTime;
use Carbon\Carbon;
use App\Models\farmTask;
use App\Models\farmActivity;
use App\Models\farms;
use Illuminate\Http\Request;

class FarmTaskController extends Controller
{
    // Display a listing of products
    public function index()
    {
        $farmActs = farmTask::all();
        return response()->json($farmActs);
    }


//     public function startTask(Request $request, $taskId)
// {
//     // Find the current task by its ID
//     $task = FarmTask::find($taskId);

//     if (!$task) {
//         return response()->json(['status' => 'error', 'message' => 'Task not found'], 404);
//     }

//     // Check if the task is already in progress or completed
//     if (in_array($task->status, ['in progress', 'completed'])) {
//         return response()->json(['status' => 'error', 'message' => 'Task is already started or completed'], 400);
//     }

//     // Mark the task as in progress and set the start time
//     $task->status = 'in progress';
//     $task->start_date = now();
//     $task->expected_end_time = now()->addDays($task->activity->period);
//     $task->save();

//     return response()->json([
//         'status' => 'success',
//         'message' => 'Task started successfully',
//         'task' => $task,
//     ], 200);
// }



//     public function completeTask(Request $request, $taskId)
//     {
//         // Find the current task by its ID
//         $task = FarmTask::find($taskId);

//         if (!$task) {
//             return response()->json(['status' => 'error', 'message' => 'Task not found'], 404);
//         }

//         // Mark the current task as completed
//         $task->status = 'completed';
//         $task->save();

//         // Get the related farm activities and current task's step
//         $farmId = $task->farm_id;
//         $currentStep = $task->activity->step;

//         // Find the next task by checking the next step
//         $nextTask = FarmTask::where('farm_id', $farmId)
//             ->whereHas('activity', function ($query) use ($currentStep) {
//                 $query->where('step', '>', $currentStep); // Get the next step
//             })
//             ->orderBy('farm_activities.step')
//             ->first();

//         // If there is a next task, set it to not started (ready to start)
//         if ($nextTask) {
//             $nextTask->status = 'in progress';
//             $nextTask->save();
//         }

//         return response()->json([
//             'message' => 'Task completed successfully',
//             'next_task' => $nextTask ? $nextTask->activity->name : 'All tasks completed'
//         ], 200);
//     }

public function startTask(Request $request, $taskId)
{
    // Find the task by its ID
    $task = FarmTask::with('activity')->find($taskId);

    if (!$task) {
        return response()->json(['status' => 'error', 'message' => 'Task not found'], 404);
    }

    if ($task->status !== 'not started') {
        return response()->json(['status' => 'error', 'message' => 'Task is already in progress or completed.'], 400);
    }

    $farmId = $task->farm_id;
    $currentStep = $task->activity->step;

    // Ensure the previous task is completed before starting this one
    $previousTask = FarmTask::where('farm_id', $farmId)
        ->whereHas('activity', function ($query) use ($currentStep) {
            $query->where('step', $currentStep - 1);
        })
        ->first();

    if ($previousTask && $previousTask->status !== 'completed') {
        return response()->json([
            'status' => 'error',
            'message' => 'Complete the previous task before starting this one.',
        ], 400);
    }

    // Ensure only one task is in progress for the same farm
    $existingInProgress = FarmTask::where('farm_id', $farmId)
        ->where('status', 'in progress')
        ->exists();

    if ($existingInProgress) {
        return response()->json([
            'status' => 'error',
            'message' => 'Another task is already in progress. Complete it before starting a new one.',
        ], 400);
    }

    // Start the task
    $task->status = 'in progress';
    $task->start_date = Carbon::now();
    $task->expected_end_time = $task->start_date->copy()->addDays(intval($task->activity->period));
    $task->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Task started successfully',
        'task' => $task,
    ], 200);
}

public function completeTask(Request $request, $taskId)
{
    // Find the task by its ID
    $task = FarmTask::with('activity')->find($taskId);

    if (!$task) {
        return response()->json(['status' => 'error', 'message' => 'Task not found'], 404);
    }

    if ($task->status !== 'in progress') {
        return response()->json(['status' => 'error', 'message' => 'Task is not in progress or already completed.'], 400);
    }

    $farmId = $task->farm_id;
    $currentStep = $task->activity->step;

    // Mark the task as completed
    $task->status = 'completed';
    $task->save();

    // Find the next task (the **exact** next step)
    $nextTask = FarmTask::where('farm_id', $farmId)
        ->whereHas('activity', function ($query) use ($currentStep) {
            $query->where('step', $currentStep + 1);
        })
        ->first();

    // If there's a next task, set it to in progress
    if ($nextTask) {
        $nextTask->status = 'in progress';
        $nextTask->start_date = Carbon::now();
        $nextTask->expected_end_time = $nextTask->start_date->copy()->addDays(intval($nextTask->activity->period));
        $nextTask->save();
    }else{
        $farm = farms::where('id',$farmId)->first();
        $farm->status = "completed";
        $farm->save();
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Task completed successfully',
        'next_task' => $nextTask ? $nextTask->activity->name : 'All tasks completed',
        'completed' => $nextTask ? false : true
    ], 200);
}





    public function getByFarmTask($id)
    {
        $farmTask = farmTask::with('activity')->where('farm_id',$id)->get();
        return response()->json($farmTask);
    }

    // Store a newly created product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_activities_id' => 'required|integer',
            'farm_id' => 'required|integer',
        ]);


        $farmTask = farmTask::create($validated);
        return response()->json(['message' => 'farmTask created successfully', 'farmType' => $farmTask], 201);
    }

    // Show a single product
    public function show($id)
    {
        $farmTask = farmTask::findOrFail($id);
        return response()->json($farmTask);
    }

    // Update an existing product
    public function update(Request $request, $id)
    {
        $farmTask = farmActivity::findOrFail($id);

        $validated = $request->validate([
            'farm_activities_id' => 'required|integer',
            'farm_id' => 'required|integer',
            'status' => 'required|string',
        ]);

        // $validated['start_date'] =  date('Y-m-d');

        $farmTask->update($validated);

        return response()->json(['message' => 'Farm Activity updated successfully', 'data' => $farmTask]);
    }

    // Delete a product
    public function destroy($id)
    {
        $farmTask = farmTask::findOrFail($id);
        $farmTask->delete();

        return response()->json(['message' => 'Farm Task deleted successfully']);
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
