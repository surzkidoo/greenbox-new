<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\wallet;
use App\Models\product;
use App\Models\shipping;
use App\Models\notification;
use Illuminate\Http\Request;
use App\Models\Adminsettings;
use App\Models\trackShipping;
use App\Models\walletTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\order;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    // Get all pending shippings where logistic is not assigned
    public function getPendingUnassigned()
    {
        $shippings = shipping::with(['item.product','item.order','logistic'])->whereNull('logistic_id')
            ->where('status', 'pending')
            ->get();

        return response()->json(['status' => 'success', 'data' => $shippings]);
    }

    // Assign a logistic to a shipping
    public function assignLogistic(Request $request, $id)
    {
        $rules = [
            'logistic_id' => 'required|exists:users,id', // Ensure logistic_id exists in users table
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


        $shipping = shipping::find($id);

        if (!$shipping) {
            return response()->json(['status' => 'error', 'message' => 'Shipping not found.'], 404);
        }

        if ($shipping->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Shipping is not in a pending state.'], 400);
        }

        if ($shipping->logistic_id) {
            return response()->json(['status' => 'error', 'message' => 'Shipping already has a logistic assigned.'], 400);
        }

        $shipping->logistic_id = $validated['logistic_id'];
        $shipping->shipped_date = Carbon::now(); // Current date and time
        $shipping->delivery_date = Carbon::now()->addDays(4); // 4 days after the shipping date
        $shipping->status = 'in-transit'; // Optionally update the status
        $shipping->save();

            //notifica product owner about the order
            $product = $shipping->item->product_id;
            $id = $shipping->item->order->user_id;

            Notification::create([
                'user_id' => $id,
                'data' => "Your order  '" . $product->name . "' is now  " . 'in-transit and assigned to logistic' . "!",
            ]);


        return response()->json(['status' => 'success', 'message' => 'Logistic assigned successfully.', 'data' => $shipping]);
    }

    public function getSingleShipping($id)
    {
        $shipping = Shipping::with(['item.product','item.order','logistic']) // Load related item and product
            ->find($id);

        if (!$shipping) {
            return response()->json(['status' => 'error', 'message' => 'Shipping not found.'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $shipping]);
    }

    // Change the status of a shipping record
    public function changeStatus(Request $request, $id)
    {
        $rules = [
            'status' => 'required|in:pending,delivered,in-transit,delayed,cancelled',
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

        $shipping = shipping::with('item')->find($id);

        if($shipping->status === "delivered"){
            return response()->json(['status' => 'error', 'message' => 'item delivered already.',]);
        }

        if (!$shipping) {
            return response()->json(['status' => 'error', 'message' => 'Shipping not found.'], 404);
        }

        $shipping->status = $validated['status'];
        $shipping->save();

        $shippingstatus = new  trackShipping();
        $shippingstatus->status = $validated['status'];
        $shippingstatus->date = Carbon::now();
        $shippingstatus->shipping_id = $shipping->id;
        $shippingstatus->save();


        //notifica product owner about the order
        $product = product::where('id',$shipping->item->product_id)->first();
        $id = $shipping->item->order->user_id;

        notification::create([
            'user_id' => $id,
            'data' => "Your order  '" . $product->name . "' is now " . $validated['status'] . "!",
        ]);

        if($validated['status'] == "delivered"){


            // DB::beginTransaction();
            // // Handle the wallet balance for the logistic
            // $wallet = wallet::where('user_id', $shipping->logistic_id)->first();
            // $wallet->update([
            //     'balance' => $shipping->item->vendor_commision,
            // ]);

            // // Log the wallet transaction
            // walletTransaction::create([
            //     'old_balance' => $wallet->balance,
            //     'new_balance' =>  $wallet->balance + $shipping->item->vendor_commision,
            //     'amount' => $shipping->item->vendor_commision,
            //     'transaction_id' => $shipping->item->id,
            //     'transaction' => 'Delivery Payout ID:-' . $shipping->id,
            //     'transaction_type' => 'Deposit',
            //     'status' => 'success',
            //     'date' => now(),
            //     'wallet_id' => $wallet->id,
            // ]);

            //Handle the vendor's wallet balance

            $product = product::where('id',$shipping->item->product_id)->first();

            $wallet = wallet::where('user_id', $product->user_id)->first();
            // Update the vendor's wallet balance

            $wallet->update([
                'balance' => $shipping->item->vendor_commision,
            ]);

            // Log the wallet transaction
            walletTransaction::create([
                'old_balance' => $wallet->balance,
                'new_balance' =>  $wallet->balance + $shipping->item->sub_total,
                'amount' => $shipping->item->sub_total,
                'transaction_id' => $shipping->item->id,
                'transaction' => 'Order Payout ID:-' . $shipping->item->order_id,
                'transaction_type' => 'Deposit',
                'status' => 'success',
                'date' => now(),
                'wallet_id' => $wallet->id,
            ]);




            $adminSetting = AdminSettings::first();

            if ($adminSetting && isset($shipping->item)) {
                $adminMoney = intval($adminSetting->admin_money) + intval($shipping->item->admin_commision);
                $insuranceMoney = intval($adminSetting->insurance_money) + intval($shipping->item->insurance);

                $adminSetting->update([
                    'admin_money' => $adminMoney,
                    'insurance_money' => $insuranceMoney,
                ]);
            }

            DB::commit();

        }

        return response()->json(['status' => 'success', 'message' => 'Status updated successfully.', 'data' => $shipping]);
    }

        // Change the status of a shipping record
    public function changeStatusorder(Request $request, $id)
    {
        $rules = [
            'status' => 'required|in:pending,delivered,in-transit,delayed,cancelled',
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

        $order = order::with('items')->find($id);

           if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found.'], 404);
        }
        //loop
        foreach ($order->items as $item) {
            $shipping = shipping::where('item_id', $item->id)->first();
            if($shipping && $shipping->status === "delivered"){
                return response()->json(['status' => 'error', 'message' => 'item delivered already.',]);
            }

            //handle money for the item
            if ($shipping && $shipping->status !== 'delivered') {
                $shipping->status = $validated['status'];
                $shipping->save();
                $shippingstatus = new  trackShipping();
                $shippingstatus->status = $validated['status'];
                $shippingstatus->date = Carbon::now();
                $shippingstatus->shipping_id = $shipping->id;
                $shippingstatus->save();
            }

            //notifica product owner about the order
            $product = product::where('id', $item->product_id)->first();
            $id = $item->order->user_id;

            notification::create([
                'user_id' => $id,
                'data' => "Your order  '" . $product->name . "' is now " . $validated['status'] . "!",
            ]);

            if ($validated['status'] == "delivered") {
                // Handle the vendor's wallet balance
                $wallet = wallet::where('user_id', $product->user_id)->first();
                // Update the vendor's wallet balance
                $wallet->update([
                    'balance' => $shipping->item->vendor_commision,
                ]);

                // Log the wallet transaction
                walletTransaction::create([
                    'old_balance' => $wallet->balance,
                    'new_balance' =>  $wallet->balance + $shipping->item->sub_total,
                    'amount' => $shipping->item->sub_total,
                    'transaction_id' => $shipping->item->id,
                    'transaction' => 'Order Payout ID:-' . $shipping->item->order_id,
                    'transaction_type' => 'Deposit',
                    'status' => 'success',
                    'date' => now(),
                    'wallet_id' => $wallet->id,
                ]);
            }


        }


        if($shipping->status === "delivered"){
            return response()->json(['status' => 'error', 'message' => 'item delivered already.',]);
        }

        if (!$shipping) {
            return response()->json(['status' => 'error', 'message' => 'Shipping not found.'], 404);
        }

        $shipping->status = $validated['status'];
        $shipping->save();

        $shippingstatus = new  trackShipping();
        $shippingstatus->status = $validated['status'];
        $shippingstatus->date = Carbon::now();
        $shippingstatus->shipping_id = $shipping->id;
        $shippingstatus->save();


        //notifica product owner about the order
        $product = product::where('id',$shipping->item->product_id)->first();
        $id = $shipping->item->order->user_id;

        notification::create([
            'user_id' => $id,
            'data' => "Your order  '" . $product->name . "' is now " . $validated['status'] . "!",
        ]);


        if($validated['status'] == "delivered"){


            // DB::beginTransaction();
            // // Handle the wallet balance for the logistic
            // $wallet = wallet::where('user_id', $shipping->logistic_id)->first();
            // $wallet->update([
            //     'balance' => $shipping->item->vendor_commision,
            // ]);

            // // Log the wallet transaction
            // walletTransaction::create([
            //     'old_balance' => $wallet->balance,
            //     'new_balance' =>  $wallet->balance + $shipping->item->vendor_commision,
            //     'amount' => $shipping->item->vendor_commision,
            //     'transaction_id' => $shipping->item->id,
            //     'transaction' => 'Delivery Payout ID:-' . $shipping->id,
            //     'transaction_type' => 'Deposit',
            //     'status' => 'success',
            //     'date' => now(),
            //     'wallet_id' => $wallet->id,
            // ]);

            //Handle the vendor's wallet balance

            $product = product::where('id',$shipping->item->product_id)->first();

            $wallet = wallet::where('user_id', $product->user_id)->first();
            // Update the vendor's wallet balance

            $wallet->update([
                'balance' => $shipping->item->vendor_commision,
            ]);

            // Log the wallet transaction
            walletTransaction::create([
                'old_balance' => $wallet->balance,
                'new_balance' =>  $wallet->balance + $shipping->item->sub_total,
                'amount' => $shipping->item->sub_total,
                'transaction_id' => $shipping->item->id,
                'transaction' => 'Order Payout ID:-' . $shipping->item->order_id,
                'transaction_type' => 'Deposit',
                'status' => 'success',
                'date' => now(),
                'wallet_id' => $wallet->id,
            ]);


            $adminSetting = AdminSettings::first();

            if ($adminSetting && isset($shipping->item)) {
                $adminMoney = intval($adminSetting->admin_money) + intval($shipping->item->admin_commision);
                $insuranceMoney = intval($adminSetting->insurance_money) + intval($shipping->item->insurance);

                $adminSetting->update([
                    'admin_money' => $adminMoney,
                    'insurance_money' => $insuranceMoney,
                ]);
            }

            DB::commit();

        }

        return response()->json(['status' => 'success', 'message' => 'Status updated successfully.', 'data' => $order]);
    }

     public function getAllShippings(Request $request)
    {
        $status = $request->query('status');

        // Fetch shippings based on the status filter if provided, otherwise fetch all
        $shippings = shipping::with(['item.product','item.order','logistic']) // Eager load relationships
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->paginate(10);

            $totalsByStatus = shipping::select('status', DB::raw('COUNT(*) as total'))
        ->groupBy('status')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->status => $item->total];
        });

            return response()->json([
                'status' => 'success',
                'totals' => $totalsByStatus,
                'data' => $shippings,
            ]);
    }


    public function GetAllUserShipping(Request $request,$userId)
    {
        $status = $request->query('status');

        // Fetch shippings based on the status filter if provided, otherwise fetch all
        $shippings = shipping::with(['item.product','item.order','logistic']) // Eager load relationships
        ->where('logistic_id', $userId)
        ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->paginate(10);

        $totalsByStatus = shipping::where('logistic_id',$userId)->select('status', DB::raw('COUNT(*) as total'))
        ->groupBy('status')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->status => $item->total];
        });

            return response()->json([
                'status' => 'success',
                'totals' => $totalsByStatus,
                'data' => $shippings,
            ]);
    }


    // Get all shipping records
    public function Trackshipping(Request $request)
    {
        // Get query parameters
        $status = $request->query('status');
        $itemId = $request->query('tracking_number');
        $orderId = $request->query('order_id');

        // Fetch shippings based on the filters provided
        $shippings = shipping::with(['item.product', 'item.order', 'logistic']) // Eager load relationships
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($itemId, function ($query) use ($itemId) {
                return $query->where('tracking_number', $itemId);

            })
            ->when($orderId, function ($query) use ($orderId) {
                return $query->whereHas('item.order', function ($q) use ($orderId) {
                    $q->where('id', $orderId);
                });
            })
            ->paginate(10); // Adjust pagination as needed

        return response()->json([
            'status' => 'successs',
            'data' => $shippings,
        ]);
    }

}

