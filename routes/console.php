<?php

use Carbon\Carbon;
use App\Models\User;
use App\Models\order;
use App\Models\Discount;
use App\Models\farmTask;
use App\Models\notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// Find orders older than 3 days with status 'pending' or 'in-complete'
Schedule::call(function () {
    $orders = order::whereIn('status', ['pending', 'in-complete'])
    ->where('created_at', '<=', Carbon::now()->subDays(3))
    ->get();

    foreach ($orders as $order) {

    $order->update(['status' => 'canceled']);

    notification::create([
        'user_id' =>$order->user_id,
        'data' => "Your Order". $order->id . "has been Cancelled",
    ]);

    }
})->daily();


Schedule::call(function () {

    // Get tasks that are past their expected end time and are still in progress or not started
    $dueTasks = farmTask::whereIn('status', ['in progress', 'not started'])
    ->where('expected_end_time', '<', Carbon::now())
    ->get();

    foreach ($dueTasks as $task) {
    // Get the user associated with the task's farm
    $user = User::find($task->farm->user_id);
    if ($user) {

        notification::create([
            'user_id' =>$user,
            'data' => "Your Farm ". $task->farm->name . "task is overdue.",
        ]);
        }
    }

})->daily();



Schedule::call(function () {
    // Retrieve all active discounts that have expired.
    $expiredDiscounts = Discount::where('is_active', true)
        ->where('discount_valid', '<', Carbon::now())
        ->get();

    foreach ($expiredDiscounts as $discount) {
        // Iterate over each discount's associated products.
        foreach ($discount->products as $product) {
            $product->d_price = $product->price;
            $product->save();
        }
    }

    // Mark all expired discounts as inactive.
    Discount::where('is_active', true)
        ->where('discount_valid', '<', Carbon::now())
        ->update(['is_active' => false]);

    // Optional: Log that the update has been run.
    //Log::info('Expired discounts processed and product prices updated.');
})->daily();

