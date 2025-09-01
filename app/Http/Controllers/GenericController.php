<?php

namespace App\Http\Controllers;

use App\Mail\OrderSuccess;
use App\Mail\Templete;
use Carbon\Carbon;
use App\Models\User;
use App\Models\order;
use App\Models\wallet;
use App\Models\payment;
use App\Models\product;
use App\Models\shipping;
use App\Models\trackOrder;
use App\Models\notification;
use App\Models\vendBusiness;
use Illuminate\Http\Request;
use App\Models\trackShipping;
use App\Models\subscriptionPlan;
use App\Models\subscriptionUser;
use App\Models\walletTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\subscriptionPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GenericController extends Controller
{


    function generateUniqueCode()
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        // Generate a 4-character random string
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Append a 4-digit timestamp (last 4 digits of Unix timestamp)
        $timestamp = substr(time(), -2);

        return $randomString . "-" . $timestamp;
    }

    /**
     * Get all states.
     */
    public function getStates()
    {
        $states = DB::table('states')->select('id', 'state_name')->get();

        if ($states->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No states found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $states]);
    }

    /**
     * Get LGAs based on state ID or name.
     */
    public function getLgasByState($state)
    {
        // Check if $state is numeric (ID) or a string (state name)

        $stateData = is_numeric($state)
            ? DB::table('states')->where('id', $state)->first()
            : DB::table('states')->where('state_name', $state)->first();

        if (!$stateData) {
            return response()->json(['status' => 'error', 'message' => 'State not found'], 404);
        }

        $lgas = DB::table('local_government_areas')
            ->where('state', $stateData->state_name)
            ->select('id', 'lga_name')
            ->get();

        if ($lgas->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No LGAs found for this state'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $lgas]);
    }

    private function verifyPaystackSignature(Request $request)
    {
        $paystackSecretKey = env('PAYSTACK_SECRET_KEY'); // Make sure to set this in your .env file

        $signature = $request->header('x-paystack-signature');
        $body = $request->getContent();

        // Create a hash using your Paystack secret key and the request body
        $hash = hash_hmac('sha512', $body, $paystackSecretKey);

        return hash_equals($signature, $hash);
    }


    public function handleWebhook(Request $request)
    {

        if (!$this->verifyPaystackSignature($request)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }


        $payload = $request->getContent();
        $payload = json_decode($payload, true);



        // Check if the event is a successful charge
        if ($payload['event'] === 'charge.success') {

            Log::info('Payment successful!', $payload);

            $transactionRef = $payload['data']['reference'];
            $amount = $payload['data']['amount'] / 100;
            $status = $payload['data']['status']; // 'success' or 'failed'
            $metadata = $payload['data']['metadata'];


        $customFields = ['custom_fields'] ?? [];
        $user_id = null;
        $payment_type = null;
        $order_id = null;


        if (!empty($metadata['custom_fields'])) {
            if (is_string($metadata['custom_fields'])) {
                $customFields = json_decode($metadata['custom_fields'], true);
            } elseif (is_array($metadata['custom_fields'])) {
                $customFields = $metadata['custom_fields'];
            }
        }

        foreach ($customFields as $field) {
            if ($field['variable_name'] === 'user_id') {
                $user_id = $field['value'];
            }
            if ($field['variable_name'] === 'payment_type') {
                $payment_type = $field['value'];
            }

            if ($field['variable_name'] === 'order_id') {
                $order_id = $field['value'];
            }

            if ($field['variable_name'] === 'subscription_id') {
                $subscriptionId = $field['value'];
            }


        }


            if ($status !== 'success') {
                return response()->json(['status' => 'error', 'message' => 'Payment failed'], 400);
            }

            // Check if it's a wallet funding or an order payment
            if ($payment_type === 'wallet') {

                if (walletTransaction::where('transaction_id', $transactionRef)->exists()) {
                    return response()->json(['status' => 'success', 'message' => 'Transaction already processed']);
                }

                return $this->handleWalletFunding($user_id, $amount, $transactionRef, $status);
            } elseif ($payment_type === 'order') {

                if (payment::where('transaction_id', $transactionRef)->exists()) {
                    return response()->json(['status' => 'success', 'message' => 'Transaction already processed']);
                }

                return $this->handleOrderPayment($order_id, $amount, $transactionRef, $status);

            } else if ($payment_type === 'subscription') {
                // Handle subscription payment
                $subscription = subscriptionPlan::where('id', $subscriptionId)->first();
                if (!$subscription) {
                    return response()->json(['status' => 'error', 'message' => 'Subscription plan not found'], 404);
                }
                // Create or update the subscription for the user
                $subscriptionUser = subscriptionUser::updateOrCreate(
                    ['user_id' => $user_id, 'subscription_plan_id' => $subscription->id],
                    [
                        'start_date' => now(),
                        'end_date' => now()->addDays($subscription->billing_cycle == 'monthly' ? 30 : 365),
                        'status' => 'active',
                        'transaction_id' => $transactionRef
                    ]
                );

                //delete any previous user subscription with the same plan name with new one
                subscriptionUser::where('user_id', $user_id)
                    ->where('subscription_plan_id', $subscription->id)
                    ->where('id', '!=', $subscriptionUser->id)->where('plan_name', $subscription->plan_name)
                    ->delete();

                // Log the subscription payment
                subscriptionPayment::create([
                    'subscription_user_id' => $subscriptionUser->id,
                    'amount' => $amount,
                    'payment_method' => 'paystack',
                    'transaction_id' => $transactionRef,
                    'status' => 'success',
                ]);

                return response()->json(['status' => 'success', 'message' => 'Subscription payment processed']);
            }


        }

        return response()->json(['status' => 'error', 'message' => 'Invalid event'], 400);
    }


    private function handleWalletFunding($userId, $amount, $transactionRef)
    {
        // Find the user's wallet
        $wallet = wallet::where('user_id', $userId)->first();

        if (!$wallet) {
            return response()->json(['status' => 'error', 'message' => 'Wallet not found'], 400);
        }

        // Update wallet balance
        DB::beginTransaction();
        $oldBalance = $wallet->balance;
        $wallet->balance += $amount;
        $wallet->save();

        // Log the transaction
        walletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_id' => $transactionRef,
            'amount' => $amount,
            'old_balance' => $oldBalance,
            'new_balance' => $wallet->balance,
            'transaction_type' => 'deposit',
            'status' => 'success',
            'date' => now(),
        ]);

        DB::commit();

        return response()->json(['status' => 'success', 'message' => 'Wallet funded successfully']);
    }


    private function handleOrderPayment($orderId, $amount, $transactionRef)
    {
        // Find the pending order
        $order = order::where('id', $orderId)->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 400);
        }

        // Update order payment status
        DB::beginTransaction();

        //create or update the order payment
        // Payment::create([
        //     'order_id' => $order->id,
        //     'payment_method' => 'credit_card',
        //     'payment_status' => 'completed',
        //     'amount' =>   $amount,
        //     'transaction_id' =>  $transactionRef
        // ]);

         //create or update the order payment
        $payment = Payment::updateOrCreate(
            ['order_id' => $order->id, 'transaction_id' => $transactionRef],
            [
                'amount' => $amount,
                'payment_method' => 'credit_card',
                'payment_status' => 'completed',
            ]
        );

        //Items Delivery settings
        foreach ($order->items as $orderItem) {
            $shipping = new shipping();
            $shipping->order_item_id = $orderItem->id;
            $shipping->tracking_number = "PY-" . $this->generateUniqueCode();
            $shipping->shipped_date = null;
            $shipping->delivery_date =  null;
            $shipping->status = 'pending';
            $shipping->save();


            $shippingstatus = new  trackShipping();
            $shippingstatus->status = 'pending';
            $shippingstatus->date = Carbon::now();
            $shippingstatus->shipping_id = $shipping->id;
            $shippingstatus->save();


                     $product = Product::find($orderItem->product_id);
                    // $phone = vendBusiness::find($product->user_id)->phone;
                    $email = User::find($product->user_id)->email;

                    //send notification to the vendor
                    Mail::to($email)->send(new Templete(
                        'You just sold a '  . $product->name . ' in Hibgreenbox',
                        'You made A Sale',
                        'You Just Got an Order',
                        'Thank you for choosing us!',
                        'Check Order',
                        'https://greenbox.com'
                    ));

                    //send order summary user
                    Mail::to($order->user->email)->send( new OrderSuccess($order));

                    //send notification to the vendor
                    notification::create([
                        'user_id' => $product->user_id,
                        'data' => "Your product '" . $product->name . "' has been purchased. Start preparing for delivery!",
                    ]);



        }



        $status = new  trackOrder();
        $status->status = 'on delivery';
        $status->date = Carbon::now();
        $status->order_id = $order->id;
        $status->save();

        DB::commit();

        return response()->json(['status' => 'success', 'message' => 'Order payment confirmed']);
    }
}
