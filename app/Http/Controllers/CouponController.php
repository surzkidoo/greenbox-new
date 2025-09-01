<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\cart;
use App\Models\coupon;
use App\Models\product;
use App\Models\cartItem;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $rules = [
            'code' => 'required|string|unique:coupons,code',
            'discount' => 'required|numeric|min:0|max:100',
            'usage' => 'required|numeric',
            'expire' => 'required|date',
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

        $rules = [
            'code' => 'string|unique:coupons,code,' . $id,
            'discount' => 'numeric|min:0|max:100',
            'usage' => 'numeric',
            'expire' => 'date',
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
        $rules = [
            'code' => 'required|string',
            'cart_id' => 'required|exists:carts,id',
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


        $coupon = coupon::where('code', $request->code)
           ->where('expire', '>=', Carbon::now()->format('Y-m-d'))
            ->first();


        if (!$coupon) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired coupon'], 404);
        }

        if($coupon->usage <= 0){
            return response()->json(['status' => 'error', 'message' => 'Used coupon'], 404);
        }

        $cart = cart::where('id', $request->cart_id)->first();



        // Fetch items from the cart
        $cartItems = cartItem::where('cart_id', $cart->id)->get();
        $total = 0;
        $totalWeight = 0;
        foreach ($cartItems as $item) {
            $product = product::find($item->product_id);
            if ($product) {
                $total += $product->getPrice() * $item->quantity;
                $totalWeight += $product->weight * $item->quantity;
            }
        }

        $discountAmount = ($coupon->discount / 100) * $total;
        $finalAmount = $total - $discountAmount;

        $cart = cart::where('id', $request->cart_id)->first();

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

    public function assignDiscountsToProducts(Request $request)
    {
        $rules = [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'discounts' => 'required|array',
            'discounts.*.discount' => 'required|numeric|min:0',
            'discounts.*.percentage' => 'required|boolean',
            'discounts.*.is_active' => 'required|boolean',
            'discounts.*.discount_valid' => 'required|date',
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

        DB::transaction(function () use ($validated) {
            // Process the discounts array once and create/retrieve discount records.
            $discountIds = [];
            foreach ($validated['discounts'] as $discountData) {
                // Check if a matching discount already exists.
                $discount = Discount::where([
                    'discount'       => $discountData['discount'],
                    'percentage'     => $discountData['percentage'],
                    'is_active'      => $discountData['is_active'],
                    'label'      => $discountData['label'],
                    'discount_valid' => $discountData['discount_valid'],
                ])->first();

                if (!$discount) {
                    // Create a new discount if not found.
                    $discount = Discount::create($discountData);
                }
                $discountIds[] = $discount->id;
            }

            // Loop over each provided product.
            foreach ($validated['products'] as $productData) {
                // Retrieve the product.
                $product = Product::find($productData['id']);

                // Attach the discounts without removing any pre-existing ones.
                $product->discounts()->syncWithoutDetaching($discountIds);

                // Determine the best (lowest) price based on active discounts.
                $bestPrice = $product->price;

                // Get active discounts (not expired) attached to this product.
                $activeDiscounts = $product->discounts()
                    ->where('is_active', true)
                    ->whereDate('discount_valid', '>=', Carbon::now())
                    ->get();

                foreach ($activeDiscounts as $discount) {
                    if ($discount->percentage) {
                        $newPrice = $product->price - ($product->price * ($discount->discount / 100));
                    } else {
                        $newPrice = $product->price - $discount->discount;
                    }
                    // Ensure that the price doesn't go negative.
                    $newPrice = max($newPrice, 0);
                    // Keep the lowest price found.
                    if ($newPrice < $bestPrice) {
                        $bestPrice = $newPrice;
                    }
                }

                // Update the product's discounted price.
                $product->d_price = $bestPrice;
                $product->save();
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Discounts assigned successfully to products, and prices updated.',
        ]);
    }

}
