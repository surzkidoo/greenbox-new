<?php

namespace App\Http\Controllers;

use App\Models\subscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    //
    public function index()
    {
        // Logic to list all subscription plans api group by plan_name
        $plans =subscriptionPlan::all();
        $plans = $plans->groupBy('plan_name')->map(function ($group) {
            return $group->first();
        })->values()->all();
        if (empty($plans)) {
            return response()->json(['message' => 'No subscription plans found'], 404);
        }
        return response()->json($plans);

    }

    //update the subscription plan
    public function update(Request $request, $id)
    {

        //validate the request
        $request->validate([
            'plan_name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'billing_cycle' => 'required|string',
            'description' => 'nullable|string|max:1000',
        ]);

        $plan = subscriptionPlan::find($id);
        if (!$plan) {
            return response()->json(['message' => 'Subscription plan not found'], 404);
        }

        $plan->update($request->all());
        return response()->json($plan);
    }

}
