<?php

use Carbon\Carbon;
use App\Models\User;
use App\Models\order;
use App\Mail\Templete;
use App\Models\Discount;
use App\Models\farmTask;
use App\Models\notification;
use App\Models\subscriptionUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Mail;
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

        // Create a notification for the user
        Mail::to($order->user->email)->send(new Templete(
            'Your order with ID ' . $order->id . ' has been cancelled due to inactivity. If you have any questions, please contact support.',
            'Order Cancellation',
            'Your order has been cancelled due to inactivity.'

        ));


        notification::create([
            'user_id' => $order->user_id,
            'data' => "Your Order" . $order->id . "has been Cancelled",
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
                'user_id' => $user,
                'data' => "Your Farm " . $task->farm->name . "task is overdue.",
            ]);

            // Send an email notification to the user
            Mail::to($user->email)->send(new Templete(
                'The task "' . $task->name . '" for your farm "' . $task->farm->name . '" is overdue. Please take action.',
                'Farm Task Overdue',
                'Your farm task is overdue.'


            ));
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


//check all users with active subscription and deactivate them if their end date has passed or send warning notification for deactivation in less than 7 days
Schedule::call(function () {
    $subscriptions = subscriptionUser::where('status', 'active')
        ->where(function ($query) {
            $query->where('end_date', '<', Carbon::now())
                ->orWhere('end_date', '<=', Carbon::now()->addDays(7));
        })
        ->get();

    foreach ($subscriptions as $subscription) {
        if ($subscription->end_date < Carbon::now()) {
            // Deactivate subscription
            $subscription->status = 'deactivated';
            $subscription->save();

            notification::create([
                'user_id' => $subscription->user_id,
                'data' => "Your subscription has been deactivated due to expiration.",
            ]);

            // Send email notification
            Mail::to($subscription->user->email)->send(new Templete(
                'Your subscription with ID ' . $subscription->id . ' has been deactivated due to expiration. If you wish to reactivate, please login and update your payment information.',
                'Subscription Deactivation',
                'Your subscription has been deactivated due to expiration.'

            ));
        } else {
            // Send warning notification
            notification::create([
                'user_id' => $subscription->user_id,
                'data' => "Your subscription will be deactivated in less than 7 days.",
            ]);

            // Send email notification
            Mail::to($subscription->user->email)->send(new Templete(
                'Your subscription with ID ' . $subscription->id . ' will be deactivated in less than 7 days. Please ensure your payment information is up to date to avoid interruption of service.',
                'Subscription Warning',
                'Your subscription will be deactivated in less than 7 days.'

            ));
        }
    }
})->daily();
