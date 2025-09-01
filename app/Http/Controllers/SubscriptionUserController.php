<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Adminsettings;

class SubscriptionUserController extends Controller
{

    //check if user has an active subscription by plan name
    public function checkUserSubscription($userid, $planName)
    {
        $user = User::find($userid);
        $adminsettings = Adminsettings::first();

        if ($adminsettings && !$adminsettings->active_subscription) {
            return response()->json(['status' => 'success', 'message' => 'Subscription feature is currently disabled'], 200);
        }

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $subscription = $user->subscriptions()
            ->whereHas('subscriptionPlan', function ($query) use ($planName) {
                $query->where('plan_name', $planName);
            })
            ->where('status', 'active')
            ->first();

        if ($subscription) {
            return response()->json(['status' => 'success', 'message' => 'User has an active subscription for this plan'], 200);
        } else {
            return response()->json(['status' => 'error', 'message' => 'User does not have an active subscription for this plan'], 404);
        }
    }

    //activate user subscription
    public function activateUserSubscription($userid, $id)
    {
        $userSubscription = User::find($userid)->subscriptions()->find($id);

        if (!$userSubscription) {
            return response()->json(['status' => 'error', 'message' => 'Subscription not found'], 404);
        }

        if ($userSubscription->isActive()) {
            return response()->json(['status' => 'error', 'message' => 'Subscription is already active'], 400);
        }

        $userSubscription->activate();
        $userSubscription->start_date = now();
        $userSubscription->end_date = now()->addDays($userSubscription->subscriptionPlan->billing_cycle == 'monthly' ? 30 : 365); // Assuming billing_cycle is in days

        return response()->json(['status' => 'success', 'message' => 'Subscription activated successfully']);
    }


    //get user subscription details and payments
    public function getUserSubscriptionDetails($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $subscriptions = $user->subscriptions()->with('subscriptionPlan', 'payments')->get();

        if ($subscriptions->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No subscriptions found for this user'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $subscriptions]);
    }

}
