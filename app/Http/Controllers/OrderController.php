<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\cart;
use App\Models\User;
use App\Models\order;
use App\Mail\Templete;
use App\Models\coupon;
use App\Models\wallet;
use App\Models\address;
use App\Models\payment;
use App\Models\product;
use App\Models\setting;
use App\Models\cartItem;
use App\Models\landmark;
use App\Models\shipping;
use App\Models\orderItems;
use App\Models\trackOrder;
use Illuminate\Support\Str;
use App\Models\notification;
use App\Models\vendBusiness;
use Illuminate\Http\Request;
use App\Models\trackShipping;
use App\Services\TwilioService;
use App\Models\walletTransaction;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller


{

    protected $paystackService;

    protected $twilio;


    public function __construct(PaystackService $paystackService, TwilioService $twilio)
    {
        $this->paystackService = $paystackService;
        $this->twilio = $twilio;
    }


    function convertToDouble($value)
    {
        // Remove commas and cast to double
        $doubleValue = (float)str_replace(',', '', $value);
        return $doubleValue;
    }

    function convertToState($value)
    {
        // Remove commas and cast to double
        if (Str::lower($value) ==  'fct') {
            return 'abuja';
        }
        return $value . ' state';
    }

    function getCart(Request $request)
    {
        if (Auth::check()) {
            // If the user is logged in, get the user's cart or create a new one
            if ($request->session_id) {

                $cart = Cart::where('session_id', $request->session_id)->first();

                if ($cart) {
                    $cart->update(['user_id' => $request->user()->id]);
                }
            } else {
                $cart = Cart::where('user_id', $request->user()->id)->first();
                if (!$cart) {
                    $session_id = Str::random(12);
                    $cart = Cart::create([
                        'user_id' => $request->user()->id,
                        'session_id' => $session_id
                    ]);
                }
            }


            return $cart;
        } else {
            // If the user is a guest, use the session to track the cart
            if ($request->session_id) {
                $session_id = $request->session_id;
            } else {
                $session_id = Str::random(12);
            }

            return Cart::firstOrCreate(['session_id' => $session_id]);
        }
    }

    public function checkout(Request $request)
    {
        try {

        // Fetch the cart for the user
        $cart = $this->getCart($request);

        $settings = setting::where('user_id', Auth::id())->first();


        $useraddress = $settings && $settings->default_shipping ? Address::find($settings->default_shipping) : null;

        if (!$cart) {
            return response()->json(['status' => 'error', 'message' => 'Cart not found for the user.'], 404);
        }

        // Fetch items and related products
        $cartItems = cartItem::where('cart_id', $cart->id)->with('product')->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Cart is empty, cannot place an order.'], 400);
        }

        // Calculate total price and weight
        $total = $cartItems->sum(fn($item) => $item->product->getPrice() * $item->quantity);
        $totalWeight = $cartItems->sum(fn($item) => $item->product->weight * $item->quantity);

        // Apply coupon if available
        $coupon = coupon::where('id', $cart->coupon_id)->where('expire', '>=', Carbon::now())->first();
        if ($coupon && $coupon->usage > 0) {
            $discountAmount = ($coupon->discount / 100) * $total;
            $total -= $discountAmount;
        }

        // Create the order
        $order = order::create([
            'user_id' => Auth::id(),
            'sub_total' => $total,
            'weight' => $totalWeight,
            'coupon_id' => $cart->coupon_id ?? null,
            'status' => 'in-complete',
        ]);

        // Calculate shipping cost
        $deliveryFee = 0;
        if ($useraddress) {
            foreach ($cartItems as $item) {
                $productOwner = $item->product->user_id;
                $pickupAddress = vendBusiness::where('user_id', $productOwner)->first();

                if (!$pickupAddress) {
                    return response()->json(['status' => 'error', 'message' => 'Pickup address not found'], 404);
                }

                // Example of fetching delivery fee data from getPricing()
                $itemDeliveryData = $this->getPricing($this->convertToState($pickupAddress->state), $this->convertToState($useraddress->state), $item->product->weight * $item->quantity, false, $pickupAddress->lga, $useraddress->lga);


                $deliveryFee = $deliveryFee + $this->convertToDouble($itemDeliveryData['delivery_fee'] ?? 0);

                orderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'item_quantity' => $item->quantity,
                    'item_weight' => $item->quantity * $item->product->weight,
                    'price' => $item->product->getPrice() * $item->quantity,
                    'delivery_fee' => $this->convertToDouble($itemDeliveryData['delivery_fee'] ?? 0),
                    'vendor_commision' => $this->convertToDouble($itemDeliveryData['vendor_fee'] ?? 0),
                    'admin_commision' => $this->convertToDouble($itemDeliveryData['admin_fee'] ?? 0),
                    'insurance' => $this->convertToDouble($itemDeliveryData['insurance_fee'] ?? 0),
                    'sub_total' => $item->quantity * $item->product->getPrice(),
                ]);
            }
            $order->total_shipping_fee = $deliveryFee;
            $order->total = $deliveryFee + $order->sub_total;
            $order->shipping_address_id = $useraddress->id;
            $order->save();
        } else {
            foreach ($cartItems as $item) {
                orderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'item_quantity' => $item->quantity,
                    'item_weight' => $item->quantity * $item->product->weight,
                    'price' => $item->product->getPrice(),
                    'sub_total' => $item->quantity * $item->product->getPrice(),
                ]);
            }
        }



        // Clean up cart after checkout
        //cartItem::where('cart_id', $cart->id)->delete();
        //cart::where('id', $cart->id)->delete();

        // Prepare the response
        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id' => $order->id,
                'address' => $useraddress,
                'total' => $total + $deliveryFee ?? 0,
                'sub_total' => $total,
                'order_items' => orderItems::with('product.images')->where('order_id', $order->id)->get(),
                'total_weight' => $totalWeight,
                'delivery_fee' => $deliveryFee ?? null,
            ],
            'message' => 'Checkout Summary',
        ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'stack' => $e->getTraceAsString()], 500);
        }

    }


    public function placeOrder(Request $request)
    {
        $rules = [
            'order_id' => 'required',
            'shipping_type' => 'required',
            'type' => 'required',
            'same_billing' => 'required|boolean',
            'landmark_id' => 'nullable|exists:landmarks,id',
            'billing.firstname' => 'nullable|string',
            'billing.lastname' => 'nullable|string',
            'billing.address' => 'nullable|string',
            'billing.s_address' => 'nullable|string',
            'billing.city' => 'nullable|string',
            'billing.state' => 'nullable|string',
            'billing.lga' => 'nullable|string',
            'billing.zip_code' => 'nullable|string',
            'billing.country' => 'nullable|string',
            'payment.status' => 'nullable|string',
            'payment.ref' => 'nullable|string',
            'payment.method' => 'required|in:credit_card,bank_transfer,greenpay',
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

        $userId = Auth::user()->id;
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        // Get Pickup Address (Default address of the product's user)
        $orderItems = $order->items;
        if ($orderItems->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No order items found'], 404);
        }


        // Determine billing address based on user's selection
        // $billingAddressID = $request->same_billing ? $order->shipping_address_id : Address::create([
        //     'user_id' => $userId,
        //     'firstname' => $request->billing['firstname'],
        //     'lastname' => $request->billing['lastname'],
        //     'address' => $request->billing['address'],
        //     'street_address' => $request->billing['s_address'],
        //     'city' => $request->billing['city'],
        //     'lga' => $request->billing['lga'],
        //     'state' => $request->billing['state'],
        //     'zip_code' => $request->billing['zip_code'],
        //     'country' => $request->billing['country'],
        // ])->id;

        // Update order with addresses
        $order->update([
            'note' => $request->shipping_note,
            'type' => $request->type,
            'shipping_method' => $request->shipping_type,
            'billing_address_id' => null,
            'landmark_id' => $request->landmark_id ?? null,
            'status' => 'pending'
        ]);

        trackOrder::where('order_id', $order->id)->delete();

        Log::info('Payment method!', $validated ?? 'No payment method provided');


        // Handle payment (as before)
        if ($request->payment['method'] === 'greenpay') {
            $wallet = wallet::where('user_id', $order->user_id)->first();

            // Check if the wallet has enough balance
            if ($wallet->balance < $order->total) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient wallet balance.',
                ], 400);
            }

            DB::beginTransaction();
            // Deduct amount from wallet
            $oldBalance = $wallet->balance;
            $newBalance = $wallet->balance - $order->total;
            $wallet->update([
                'balance' => $newBalance,
            ]);

            // Log the wallet transaction
            walletTransaction::create([
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'amount' => $order->total,
                'transaction_type' => 'withdraw',
                'status' => 'success',
                'date' => now(),
                'wallet_id' => $wallet->id,
            ]);

            DB::commit();

            // Create a payment record
            payment::create([
                'order_id' => $order->id,
                'payment_method' => 'greenpay',
                'transaction_id' => "PY-" . $this->generateUniqueCode(),
                'payment_status' => 'completed',
                'amount' => $order->total,
            ]);

            //Items Delivery settings
            foreach ($order->items as $orderItem) {
                $shipping = new shipping();
                $shipping->order_item_id = $orderItem->id;
                $shipping->tracking_number = "TRK-" . $this->generateUniqueCode();
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
                $product = Product::find($orderItem->product_id);
                // $phone = vendBusiness::find($product->user_id)->phone;
                notification::create([
                    'user_id' => $product->user_id,
                    'data' => "Your product '" . $product->name . "' has been purchased. Start preparing for delivery!",
                ]);
            }

            $status = new  trackOrder();
            $status->status = 'order placed';
            $status->date = Carbon::now();
            $status->order_id = $order->id;
            $status->save();

            $status = new  trackOrder();
            $status->status = 'pending confirmation';
            $status->order_id = $order->id;
            $status->date = Carbon::now();
            $status->save();

            $status = new  trackOrder();
            $status->status = 'on delivery';
            $status->date = Carbon::now();
            $status->order_id = $order->id;
            $status->save();

            $order->status = 'on_delivery';
            $order->save();

        } else if ($request->payment['method'] === 'credit_card') {

            $paystackResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            ])->get("https://api.paystack.co/transaction/verify/" . $request->payment['ref'] . "");

            if ($paystackResponse->successful() && $paystackResponse->json('data')['status'] === 'success') {
                $amount = $paystackResponse->json('data')['amount'] / 100;

                payment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'credit_card',
                    'payment_status' => 'pending',
                    'amount' =>   $amount,
                    'transaction_id' =>  $request->payment['ref']
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

                    $product = Product::find($orderItem->product_id);
                    // $phone = vendBusiness::find($product->user_id)->phone;
                    $email = User::find($product->user_id)->email;


                    // Mail::to($email)->send(new Templete(
                    //     'You just sold a '  . $product->name . ' in Hibgreenbox',
                    //     'You made A Sale',
                    //     'You Just Got an Order',
                    //     'Thank you for choosing us!',
                    //     'Check Order',
                    //     'https://greenbox.com'
                    // ));


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


            } else {
                return response()->json(['status' => 'error', 'message' => 'Payment verification failed'], 400);
            }
        } elseif ($request->payment['method'] === 'bank_transfer') {

            payment::create([
                'order_id' => $order->id,
                'payment_method' => 'bank_transfer',
                'payment_status' => 'pending',
                'amount' => $order->total,
                'transaction_id' => "PY-" . $this->generateUniqueCode(),
            ]);

            $status = new  trackOrder();
            $status->status = 'order placed';
            $status->date = Carbon::now();
            $status->order_id = $order->id;
            $status->save();

            $status = new  trackOrder();
            $status->status = 'pending confirmation';
            $status->order_id = $order->id;
            $status->date = Carbon::now();
            $status->save();
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment method Required!',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order placed successfully!',
            'order_id' => $order->id
        ], 201);
    }



    public function getShipping(Request $request)
    {
        $rules = [
            'express' => 'required|boolean',
            'address_id' => 'required|integer',  // Assuming it's an address ID, not user ID.
            'order_id' => 'required|integer',
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


        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        $order = order::with('Items.product')->findOrFail($validated['order_id']);

        $deliveryFee = 0;

        // Retrieve the shipping address
        $address = Address::find($validated['address_id']);

        if (!$address) {
            return response()->json([
                'status' => 'error',
                'message' => 'No shipping address found'
            ], 404);
        }

        foreach ($order->Items as $item) {
            $pickupAddress = vendBusiness::where('user_id', $item->product->user_id)->first();

            if (!$pickupAddress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pickup address not found'
                ], 404);
            }

            // $itemDeliveryData = $this->getPricing(
            //     $pickupAddress->state,
            //     $address->state,
            //     $item->product->weight * $item->quantity,
            //     $validated['express'],
            //     $pickupAddress->lga,
            //     $address->lga
            // );


            $itemDeliveryData = $this->getPricing(
                $this->convertToState($pickupAddress->state),
                $this->convertToState($address->state),
                $item->product->weight,
                $request->express,
                $pickupAddress->lga,
                $address->lga
            );



            $deliveryFee +=  $this->convertToDouble($itemDeliveryData['delivery_fee'] ?? 0);
            $item->update([
                'delivery_fee' => $this->convertToDouble($itemDeliveryData['delivery_fee'] ?? 0),
                'vendor_commision' => $this->convertToDouble($itemDeliveryData['vendor_fee'] ?? 0),
                'admin_commision' => $this->convertToDouble($itemDeliveryData['admin_fee'] ?? 0),
                'insurance' => $this->convertToDouble($itemDeliveryData['insurance_fee'] ?? 0)
            ]);
        }

        $order->total_shipping_fee = $deliveryFee;
        $order->total = $deliveryFee + $order->sub_total;
        $order->shipping_address_id = $address->id;
        $order->save();


        return response()->json([
            'status' => 'success',
            'message' => 'Shipping calculation completed',
            'delivery_fee' => $deliveryFee,
            'sub_total' => $order->sub_total + $deliveryFee,
            'address' => $address,
        ]);
    }


    // Get order details by ID
    public function getOrder($id)
    {
        $order = order::with(['items.product.user', 'items.product.user.vendBusiness', 'items.shipping', 'billingAddress', 'shippingAddress', 'payment'])->find($id);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'order' => $order,
        ]);
    }

    //change order status
    // public function changeOrderStatus(Request $request, $id)
    // {
    //     // ['in-complete','pending', 'completed', 'on_delivery', 'delivered', 'cancelled','refund','refunded','ended']
    //     $rules = [
    //         'status' => 'required|in:in-complete,pending,completed,on_delivery,delivered,cancelled,refund,refunded,ended',
    //     ];
    //     // Create the validator instance
    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) {
    //         // Customize the error response for API requests
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }
    //     $validated = $validator->validated();

    //     $order = order::find($id);

    //     if (!$order) {
    //         return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
    //     }

    //     $order->status = $request->input('status');
    //     $order->save();

    //     // Create a new trackOrder entry for the status change
    //     if($order->status === "delivered"){
    //         return response()->json(['status' => 'error', 'message' => 'item delivered already.',]);
    //     }

    //     $id = $order->user_id;

    //     notification::create([
    //         'user_id' => $id,
    //         'data' => "Your order  '" . $order->id . "' is now " . $order->status . "!",
    //     ]);

    //     //SEND NOTIFICATION TO SELLER LOOP
    //     foreach ($order->items as $orderItem) {
    //         $product = product::where('id', $orderItem->product_id)->first();
    //         $sellerId = $product->user_id;

    //         notification::create([
    //             'user_id' => $sellerId,
    //             'data' => "Your product  '" . $product->name . "' is now " . $order->status . "!",
    //         ]);

    //     }
    //        if($validated['status'] == "delivered"){

    //         //Handle the vendor's wallet balance


    //         $wallet = wallet::where('user_id', $product->user_id)->first();
    //         // Update the vendor's wallet balance

    //         $wallet->update([
    //             'balance' => $shipping->item->vendor_commision,
    //         ]);

    //         // Log the wallet transaction
    //         walletTransaction::create([
    //             'old_balance' => $wallet->balance,
    //             'new_balance' =>  $wallet->balance + $shipping->item->sub_total,
    //             'amount' => $shipping->item->sub_total,
    //             'transaction_id' => $shipping->item->id,
    //             'transaction' => 'Order Payout ID:-' . $shipping->item->order_id,
    //             'transaction_type' => 'Deposit',
    //             'status' => 'success',
    //             'date' => now(),
    //             'wallet_id' => $wallet->id,
    //         ]);




    //         $adminSetting = AdminSettings::first();

    //         if ($adminSetting && isset($shipping->item)) {
    //             $adminMoney = intval($adminSetting->admin_money) + intval($shipping->item->admin_commision);
    //             $insuranceMoney = intval($adminSetting->insurance_money) + intval($shipping->item->insurance);

    //             $adminSetting->update([
    //                 'admin_money' => $adminMoney,
    //                 'insurance_money' => $insuranceMoney,
    //             ]);
    //         }

    //         DB::commit();

    //     }

    //     return response()->json(['status' => 'success', 'message' => 'Order status updated successfully']);
    // }

    // Admin Approve Payment
    public function approvePayment($id)
    {
        $order = order::find($id);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        $payment = payment::where('order_id', $id)->first();

        $payment->payment_status = "completed";
        $payment->save();


        $userEmail = $order->user->email;

        Mail::to($userEmail)->send(new Templete(
            'Your Payment has been confirm for order '  . $order->id . ' we processing to shipping',
            'Order Confirmed',
            'Order Confirmation',
            'Thank you for choosing us!',
            'Check Order Status',
            'https://greenbox.com'
        ));


        //  payment::create([
        //     'order_id' => $order->id,
        //     'payment_method' => 'credit_card',
        //     'payment_status' => 'completed',
        //     'amount' =>   $amount,
        //     'transaction_id' =>  $request->payment['ref']
        // ]);

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

            $product = Product::find($orderItem->product_id);
            $phone = vendBusiness::find($product->user_id)->phone;
            $email = User::find($product->user_id)->email;


            Mail::to($email)->send(new Templete(
                'You just sold a '  . $product->name . '  in Hibgreenbox',
                'You made A Sale',
                'You Just Got an Order',
                'Thank you for choosing us!',
                'Check Order',
                'https://greenbox.com'
            ));


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


        return response()->json([
            'status' => 'success',
            'order' => $order,
        ]);
    }


    // List all orders for a user
    public function listOrders(Request $request)
    {
        $statuses = $request->query('status', ['pending', 'in-complete', 'completed', 'on_delivery', 'delivered', 'canceled']);

        // Ensure statuses are always an array
        $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);

        // Validate the statuses to ensure they match allowed values
        // $request->validate([
        //     'status' => 'array',
        //     'status.*' => 'in:pending,completed,on_delivery,delivered,canceled',
        // ]);

        // Query orders based on user ID and the provided statuses
        $orders = order::whereIn('status', $statuses)
            ->with(['items.product.user', 'items.product.user.vendBusiness', 'items.product.images', 'items.shipping', 'billingAddress', 'shippingAddress', 'payment'])
            ->paginate(10); // Adjust the pagination count as needed


        // Total Orders
        $totalOrders = DB::table('orders')->count();

        // Total Volume (Sum of weights)
        $totalVolume = DB::table('orders')->sum('sub_total');

        // Total Fulfilled Orders (status: 'completed' or 'delivered')
        $totalFulfilledOrders = DB::table('orders')
            ->whereIn('status', ['completed', 'delivered'])
            ->count();

        // Total Customers (Distinct user_ids who have placed orders)
        $totalCustomers = DB::table('orders')
            ->distinct('user_id')
            ->count('user_id');

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $orders,
                'total_orders' => $totalOrders,
                'total_volume' => $totalVolume,
                'total_fulfilled_orders' => $totalFulfilledOrders,
                'total_customers' => $totalCustomers,
            ],
        ]);
    }


    // List all orders for a user
    public function invoiceOrders(Request $request)
    {
        $statuses = $request->query('status', ['pending', 'completed', 'on_delivery', 'delivered', 'canceled']);

        // Ensure statuses are always an array
        $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);

        // Validate the statuses to ensure they match allowed values
        // $request->validate([
        //     'status' => 'array',
        //     'status.*' => 'in:pending,completed,on_delivery,delivered,canceled',
        // ]);

        // Query orders based on user ID and the provided statuses
        $orders = order::whereIn('status', $statuses)
            ->with(['items.product.user','items.product.images', 'items.product.user.vendBusiness', 'items.shipping', 'billingAddress', 'shippingAddress', 'payment'])
            ->paginate(10); // Adjust the pagination count as needed

        // Total Invoices
        $totalInvoices = DB::table('orders')->count();

        // Total Paid Invoices
        $totalPaidInvoices = DB::table('orders')
            ->whereIn('status', ['completed', 'delivered'])
            ->count();

        // Total Unpaid Invoices
        $totalUnpaidInvoices = DB::table('orders')
            ->where('status', 'pending')
            ->count();

        // Total Amount (Sum of totals for all invoices)
        $totalAmount = DB::table('orders')->sum('total');

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $orders,
                'total_invoices' => $totalInvoices,
                'total_paid_invoices' => $totalPaidInvoices,
                'total_unpaid_invoices' => $totalUnpaidInvoices,
                'total_amount' => $totalAmount,

            ],
        ]);
    }


    // Get orders by user ID for products owned by that user
    public function getOrdersByVendor(Request $request, $userId)
    {
        $statuses = $request->query('status', ['pending', 'completed', 'on_delivery', 'delivered', 'canceled']);

        // Ensure statuses are always an array
        $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);

        // First, find all products owned by the user
        $products = product::where('user_id', $userId)->pluck('id');

        // If the user has no products, return a message
        if ($products->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No products found for this user.'], 404);
        }

        // Get all orders that contain the products owned by the user
        $orders = order::whereHas('items', function ($query) use ($products) {
            $query->whereIn('product_id', $products);
        })
            ->with(['items.product.user','items.product.images', 'items.product.user.vendBusiness', 'items.shipping', 'billingAddress', 'shippingAddress', 'payment'])->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }


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



    public function getPricing($stateFrom, $stateTo, $weightKg, $express, $lgfrom, $lgto)
    {
        try {
            $weightTier = $this->getWeightTier2($weightKg);

            // Check if both pickup and destination states are the same
            if ($stateFrom == $stateTo) {

                $distance = $this->getDistance($stateFrom, $stateTo, $lgfrom, $lgto);


                if ($distance <= 50) {
                    $weightTier = $this->getWeightTier3($weightTier);
                    $distanceBand = "1 - 50 km (In city)";
                } else {
                    $weightTier = $this->getWeightTier2($weightKg);
                    $distanceBand = "1 - 99 km";
                }

                return $this->fetchPricing('incity', $distanceBand, 'to', $weightTier, $express);
            }

            // For interstate deliveries
            return $this->fetchPricing('pricing', $stateFrom, $stateTo, $weightTier, $express);
        } catch (\Exception $e) {
            // Log the error for debugging

            // Return a default error response
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get distance between two locations using Google Maps API.
     */
    private function getDistance($stateFrom, $stateTo, $lgfrom, $lgto)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY'); // Ensure the key is set in .env

        $response = Http::withOptions([
            'verify' => false
        ])->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $lgfrom . ", " . $stateFrom,
            'destinations' => $lgto . ", " . $stateTo,
            'key' => $apiKey,
        ]);


        // $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
        //     'origins' => $lgfrom . ", " . $stateFrom,
        //     'destinations' => $lgto . ", " . $stateTo,
        //     'key' => $apiKey,
        // ]);


        if ($response->successful()) {
            $data = $response->json();

            if (!empty($data['rows'][0]['elements'][0]['distance'])) {
                $distanceText = $data['rows'][0]['elements'][0]['distance']['text'];

                return round((float) filter_var($distanceText, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
            }
        }

        throw new \Exception("Failed to fetch distance from Google Maps API.");
    }

    /**
     * Fetch pricing details from the database.
     */
    private function fetchPricing($table, $from, $to = null, $weightTier, $express)
    {
        $query = DB::table($table)
            ->where('weight_tier_kg', $weightTier);

        if ($table == 'incity') {
            $query->whereRaw('LOWER(distance_band_km) = ?', [strtolower($from)]);
        } else {
            $query->whereRaw('LOWER(state_from) = ?', [strtolower($from)])
                ->whereRaw('LOWER(state_to) = ?', [strtolower($to)]);
        }

        $pricing = $query->first();


        if (!$pricing) {
            throw new \Exception("Pricing details not found for the given parameters.");
        }


        if ($table == 'incity') {
            return [
                'delivery_fee' => $express ? $pricing->Express_Prices_2 : $pricing->Standard_Prices_2,
                'insurance_fee' => $express ? $pricing->Express_Insurance_4 : $pricing->Standard_Insurance_3,
                'vendor_fee' => $express ? $pricing->Express_Prices : $pricing->Standard_Prices,
                'admin_fee' => $pricing->Service_Charges,
            ];
        }

        return [
            'delivery_fee' => $express ? $pricing->Express_Prices_2 : $pricing->Standard_Prices_2,
            'insurance_fee' => $express ? $pricing->Express_Insurance_4 : $pricing->Standard_Insurance_3,
            'vendor_fee' => $express ? $pricing->Express_Prices_1 : $pricing->Standard_Prices_1,
            'admin_fee' => $pricing->Service_Charges,
        ];
    }


    private function getDistanceBand($distanceKm)
    {
        // Return a distance band based on the distance
        if ($distanceKm >= 1 && $distanceKm <= 50) {
            return '1 - 50 km';
        } elseif ($distanceKm >= 51 && $distanceKm <= 99) {
            return '51 - 99 km';
        }
        return 'Unknown';
    }

    private function getWeightTier($weightKg)
    {
        // Return weight tier based on weight
        if ($weightKg <= 50) {
            return '0-50';
        } elseif ($weightKg <= 100) {
            return '51-100';
        } elseif ($weightKg <= 200) {
            return '101-200';
        }
        return 'Unknown';
    }

    private function getWeightTier2($weightKg)
    {
        // Return weight tier based on weight
        if ($weightKg <= 50) {
            return '0-50';
        } elseif ($weightKg <= 100) {
            return '51-200';
        } elseif ($weightKg <= 50000) {
            return '201-500';
        } elseif ($weightKg <= 1000) {
            return '501-1000';
        }
        return '501-1000';
    }

    private function getWeightTier3($weightKg)
    {
        // Return weight tier based on weight
        if ($weightKg <= 5) {
            return '0-5';
        } elseif ($weightKg <= 10) {
            return '6-10';
        } elseif ($weightKg <= 20) {
            return '11-20';
        } elseif ($weightKg <= 50) {
            return '21-50';
        } elseif ($weightKg <= 100) {
            return '51-100';
        }
        return '52-100';
    }

    //next featues
    public function createAndSendOrder(Request $request)
    {
        $validated = $request->validate([
            // 'user_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping.type' => 'required|string|in:standard,express',
            'shipping.note' => 'nullable|string',
            'shipping.address_id' => 'required|exists:addresses,id',
            'payment.method' => 'required|string|in:credit_card,bank_transfer,greenpay',
            'payment.ref' => 'nullable|string', // Required for specific payment methods
        ]);

        try {
            DB::beginTransaction();

            $validated['user_id'] = Auth::id();

            // Create the order
            $order = order::create([
                'user_id' => $validated['user_id'],
                'total' => 0, // This will be updated after items are added
                'status' => 'pending',
                'shipping_method' => $validated['shipping']['type'],
                'shipping_note' => $validated['shipping']['note'] ?? null,
                'shipping_address_id' => $validated['shipping']['address_id'],
            ]);

            $total = 0;
            $totalShippingFee = 0; // Initialize shipping fee accumulator

            // Add order items
            foreach ($validated['items'] as $item) {
                $product = product::findOrFail($item['product_id']);
                $subtotal = $product->getPrice() * $item['quantity'];
                $total += $subtotal;



                $pickupAddress = vendBusiness::where('user_id', $item['product_id'])->first();

                if (!$pickupAddress) {
                    return response()->json(['status' => 'error', 'message' => 'Pickup address not found'], 404);
                }

                $useraddress = address::where('id', $validated['shipping']['address_id'])->first();

                $shippingData = $this->getPricing($this->convertToState($pickupAddress->state), $this->convertToState($useraddress->state), $item->product->weight * $item['quantity'], $validated['shipping']['type'] === 'express', $pickupAddress->lga, $useraddress->lga);

                $deliveryFee = $this->convertToDouble($shippingData['delivery_fee'] ?? 0);
                $totalShippingFee += $deliveryFee;

                // Create order item with shipping cost
                orderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'item_quantity' => $item['quantity'],
                    'item_weight' => $item->quantity * $item->product->weight,
                    'price' => $item->product->getPrice() * $item['quantity'],
                    'delivery_fee' => $this->convertToDouble($shippingData['delivery_fee'] ?? 0),
                    'vendor_commision' => $this->convertToDouble($shippingData['vendor_fee'] ?? 0),
                    'admin_commision' => $this->convertToDouble($shippingData['admin_fee'] ?? 0),
                    'insurance' => $this->convertToDouble($shippingData['insurance_fee'] ?? 0),
                    'sub_total' => $item->quantity * $item->product->getPrice(),
                ]);
            }

            // Update order total with shipping fee
            $order->update(['total' => $total + $totalShippingFee]);

            // Process payment
            if ($validated['payment']['method'] === 'greenpay') {
                $wallet = Wallet::where('user_id', $order->user_id)->first();

                if ($wallet->balance < $total + $totalShippingFee) {
                    return response()->json(['status' => 'error', 'message' => 'Insufficient wallet balance.'], 400);
                }

                $wallet->update(['balance' => $wallet->balance - ($total + $totalShippingFee)]);
                payment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'greenpay',
                    'transaction_id' => "TRX-" . Str::random(8),
                    'payment_status' => 'completed',
                    'amount' => $total + $totalShippingFee,
                ]);
            } elseif ($validated['payment']['method'] === 'credit_card') {
                $paystackResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                ])->get("https://api.paystack.co/transaction/verify/" . $validated['payment']['ref']);

                if (!$paystackResponse->successful() || $paystackResponse->json('data')['status'] !== 'success') {
                    return response()->json(['status' => 'error', 'message' => 'Payment verification failed.'], 400);
                }

                payment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'credit_card',
                    'transaction_id' => $validated['payment']['ref'],
                    'payment_status' => 'completed',
                    'amount' => $total + $totalShippingFee,
                ]);
            } elseif ($validated['payment']['method'] === 'bank_transfer') {
                payment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'bank_transfer',
                    'payment_status' => 'pending',
                    'transaction_id' => "TRX-" . Str::random(8),
                    'amount' => $total + $totalShippingFee,
                ]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid payment method.'], 400);
            }

            // Create shipping details
            foreach ($order->items as $orderItem) {
                shipping::create([
                    'order_item_id' => $orderItem->id,
                    'tracking_number' => "TRK-" . Str::random(8),
                    'status' => 'in-transit',
                    'logistic_id' => $validated['logistic_id'],

                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order created and shipping assigned successfully!',
                'order_id' => $order->id,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create and send order: ' . $e->getMessage(),
            ], 500);
        }
    }
}
