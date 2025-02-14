<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
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
use App\Models\walletTransaction;
use Illuminate\Support\Facades\DB;

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


    public function handleWebhook(Request $request)
{
    $payload = $request->all();

    // Check if the event is a successful charge
    if ($payload['event'] === 'charge.success') {
        $transactionRef = $payload['data']['reference'];
        $amount = $payload['data']['amount'] / 100;
        $status = $payload['data']['status']; // 'success' or 'failed'
        $metadata = $payload['data']['metadata'];

        if ($status !== 'success') {
            return response()->json(['status' => 'error', 'message' => 'Payment failed'], 400);
        }

        // Check if it's a wallet funding or an order payment
        if ($metadata['payment_type'] === 'wallet') {

            if (walletTransaction::where('transaction_id', $transactionRef)->exists()) {
                return response()->json(['status' => 'success', 'message' => 'Transaction already processed']);
            }

            return $this->handleWalletFunding($metadata['user_id'], $amount, $transactionRef);

        } elseif ($metadata['payment_type'] === 'order') {

            if (payment::where('transaction_id', $transactionRef)->exists()) {
                return response()->json(['status' => 'success', 'message' => 'Transaction already processed']);
            }

            return $this->handleOrderPayment($metadata['user_id'], $amount, $transactionRef);
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
        'status' => 'completed',
        'date' => now(),
    ]);

    DB::commit();

    return response()->json(['status' => 'success', 'message' => 'Wallet funded successfully']);
}


private function handleOrderPayment($userId, $amount, $transactionRef)
{
    // Find the pending order
    $order = order::where('user_id', $userId)->where('status', 'pending')->first();

    if (!$order) {
        return response()->json(['status' => 'error', 'message' => 'Order not found'], 400);
    }

    // Update order payment status
    DB::beginTransaction();


        Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'credit_card',
            'payment_status' => 'completed',
            'amount' =>   $amount,
            'transaction_id' =>  $transactionRef
        ]);

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



            //notifica product owner about the order

            $product = product::find($orderItem->product_id);
            $phone = vendBusiness::find($product->user_id)->phone;
            notification::create([
                'user_id' => $product->user_id,
                'data' => "Your product '" . $product->name . "' has been purchased. Start preparing for delivery!",
            ]);
        }

        //Placed
        $status = new  trackOrder();
        $status->status = 'order placed';
        $status->date = Carbon::now();
        $status->order_id = $order->id;
        $status->save();

        //Placed
        $status = new  trackOrder();
        $status->status = 'pending confirmation';
        $status->date = Carbon::now();
        $status->order_id = $order->id;
        $status->save();

        $status = new  trackOrder();
        $status->status = 'on delivery';
        $status->date = Carbon::now();
        $status->order_id = $order->id;
        $status->save();

    DB::commit();

    return response()->json(['status' => 'success', 'message' => 'Order payment confirmed']);
}

}
