<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\cart;
use App\Models\coupon;
use App\Models\product;
use App\Models\cartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    /**
     * List all coupons.
     */
    public function index()
    {
        $coupons = coupon::all();
        return response()->json(['status' => 'success', 'data' => $coupons]);
    }

    /**
     * Create a new coupon.
     */
    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:coupons,code',
            'discount' => 'required|numeric|min:0|max:100',
            'usage' => 'required|numeric',
            'expire' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $coupon = coupon::create($request->only(['code', 'discount', 'expire','usage']));

        return response()->json(['status' => 'success', 'data' => $coupon], 201);
    }

    /**
     * Show a single coupon.
     */
    public function show($id)
    {
        $coupon = coupon::find($id);

        if (!$coupon) {
            return response()->json(['status' => 'error', 'message' => 'coupon not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $coupon]);
    }

    /**
     * Update a coupon.
     */
    public function update(Request $request, $id)
    {
        $coupon = coupon::find($id);

        if (!$coupon) {
            return response()->json(['status' => 'error', 'message' => 'coupon not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'string|unique:coupons,code,' . $id,
            'discount' => 'numeric|min:0|max:100',
            'usage' => 'numeric',
            'expire' => 'date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $coupon->update($request->only(['code', 'discount', 'expire','usage']));

        return response()->json(['status' => 'success', 'data' => $coupon]);
    }

    /**
     * Delete a coupon.
     */
    public function destroy($id)
    {
        $coupon = coupon::find($id);

        if (!$coupon) {
            return response()->json(['status' => 'error', 'message' => 'coupon not found'], 404);
        }

        $coupon->delete();

        return response()->json(['status' => 'success', 'message' => 'coupon deleted successfully']);
    }

    /**
     * Calculate the discount for a given coupon code and amount.
     */
    public function calculateDiscount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $coupon = coupon::where('code', $request->code)
           ->where('expire', '>=', Carbon::now()->format('Y-m-d'))
            ->first();




        if (!$coupon) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired coupon'], 404);
        }

        if($coupon->usage <= 0){
            return response()->json(['status' => 'error', 'message' => 'Used coupon'], 404);
        }

        $cart = cart::where('user_id', Auth::id())->first();
        // Fetch items from the cart
        $cartItems = cartItem::where('cart_id', $cart->id)->get();
        $total = 0;
        $totalWeight = 0;
        foreach ($cartItems as $item) {
            $product = product::find($item->product_id);
            if ($product) {
                $total += $product->price * $item->quantity;
                $totalWeight += $product->weight * $item->quantity;
            }
        }

        $discountAmount = ($coupon->discount / 100) * $total;
        $finalAmount = $total - $discountAmount;

        $cart = cart::where('user_id',Auth::user()->id)->first();

        if(!$cart){
            return response()->json([
                'status' => 'success',
                'message' =>'Coupon can be Applied to Empty Cart'
            ]);
        }

        $cart->coupon_id = $coupon->id;
        $cart->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'original_amount' => $total,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'coupon' => $coupon->code,
            ],
        ]);
    }
}
