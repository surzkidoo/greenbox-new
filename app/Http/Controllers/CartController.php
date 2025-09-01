<?php
// app/Http/Controllers/CartController.php
namespace App\Http\Controllers;

use App\Models\cart;
use App\Models\product;
use App\Models\cartItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
protected function getCart(Request $request)
{
    if (!Auth::check()) {
        return null; // Return null if user is not authenticated
    }

    // Retrieve the cart by user_id
    $cart = cart::where('user_id', $request->user()->id)->first();

    // If no cart exists, create a new one
    if (!$cart) {
        $cart = cart::create([
            'user_id' => $request->user()->id,
        ]);
    }

    return $cart;

    }

    // Add a product to the cart
    public function addToCart(Request $request)
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
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

        // Get the current cart
        $cart = $this->getCart($request);

        // Check if the product is already in the cart
        $cartItem = cartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $validated['product_id'],
        ]);

        $product = product::where('id',$validated['product_id'])->first();

        if(boolval($product->available) ==false || boolval($product->active) == false ){
            return response()->json(['status'=> 'error', 'message' => 'Product is not available for purchase']);
        }

        if($product->stock_available < $validated['quantity'] && $product->availability_type!="unlimited" ){
            return response()->json(['status'=> 'error', 'message' => 'Units unavailable']);
        }

        // Update the quantity or set it to the requested amount
        $cartItem->quantity += $validated['quantity'];
        $cartItem->save();

        return response()->json(['status'=> 'success','message' => 'Product added to cart', 'data' => [ 'cart' => $cart->load('items.product.images')]]);
    }

public function viewCart(Request $request)
{
    $cart = $this->getCart($request);

    if (!$cart) {
        return response()->json(['status' => 'error', 'message' => 'Cart not found']);
    }

    $cart->load('items.product.images'); // Eager load items and their products

    return response()->json(['status' => 'success', 'cart' => $cart]);
}

    // Update the quantity of a cart item
    public function updateCartItem(Request $request, $cartItemId)
    {
        $rules = [
            'quantity' => 'required|integer|min:0',
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

        $cartItem = cartItem::findOrFail($cartItemId);

        $product = product::where('id',$cartItem->product_id)->first();

        if($product->stock_available < $validated['quantity'] && $product->availability_type!="unlimited" ){

            return response()->json(['status'=> 'error', 'message' => 'Units unavailable']);
        }

        $cartItem->quantity = $validated['quantity'];
        $cartItem->save();

        return response()->json(['status'=> 'success','message' => 'Cart item updated', 'cartItem' => $cartItem]);
    }

    // Remove an item from the cart
    public function removeCartItem($cartItemId)
    {
        $cartItem = cartItem::findOrFail($cartItemId);
        $cartItem->delete();

        return response()->json(['status'=> 'success','message' => 'Cart item removed']);
    }
}
