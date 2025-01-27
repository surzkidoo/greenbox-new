<?php
// app/Http/Controllers/CartController.php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\product;
use App\Models\CartItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // Get the current user's cart or guest's cart using session
    protected function getCart(Request $request)
    {
        if ($request->user_id) {
            // If the user is logged in, get the user's cart or create a new one
            if ($request->session_id) {

                $sessionCart = Cart::where('session_id', $request->session_id)->first();

                if ($sessionCart) {
                    $sessionCart->update(['user_id' => $request->user_id]);
                }

            }

            return Cart::firstOrCreate(['user_id' => $request->user_id]);

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
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

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
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

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
