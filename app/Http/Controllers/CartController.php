<?php
// app/Http/Controllers/CartController.php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\product;
use App\Models\CartItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    // Get the current user's cart or guest's cart using session
    protected   function getCart(Request $request)
    {
        if (Auth::check()) {
            // If the user is logged in, get the user's cart or create a new one
            if ($request->session_id) {

                $cart = Cart::where('session_id', $request->session_id)->first();

                if ($cart) {
                    $cart->update(['user_id' => $request->user()->id]);
                }

            }

            else{
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
            if($request->session_id){
                $session_id = $request->session_id;
            }else{
                $session_id = Str::random(12);
            }

            return Cart::firstOrCreate(['session_id' => $session_id]);
        }
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
        $cartItem = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $validated['product_id'],
        ]);

        $product = product::where('id',$validated['product_id'])->first();



        if($product->stock_available < $validated['quantity'] && $product->availability_type!="unlimited" ){
            return response()->json(['status'=> 'error', 'message' => 'Units unavailable']);
        }

        // Update the quantity or set it to the requested amount
        $cartItem->quantity += $validated['quantity'];
        $cartItem->save();

        return response()->json(['status'=> 'success','message' => 'Product added to cart', 'data' => [ 'cart' => $cart->load('items')]]);
    }

    // Get all cart items
    public function viewCart(Request $request)
    {
        $cart = $this->getCart($request);

        return response()->json(['status'=> 'success','cart' => $cart->with('items.product')->get()]);
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

        $cartItem = CartItem::findOrFail($cartItemId);

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
        $cartItem = CartItem::findOrFail($cartItemId);
        $cartItem->delete();

        return response()->json(['status'=> 'success','message' => 'Cart item removed']);
    }
}
