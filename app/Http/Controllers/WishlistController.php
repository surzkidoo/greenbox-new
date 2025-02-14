<?php

namespace App\Http\Controllers;

use App\Models\product;
use App\Models\wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::with('product.images')
        ->where('user_id', Auth::id())
        ->get();

        $wishlistCount = Wishlist::with('product.images')
        ->where('user_id', Auth::id())
        ->Count();


        $totalProductsBought = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.user_id', Auth::id())
        ->where('orders.status', 'completed') // Only count products from completed orders
        ->get();

        $totalProductsBoughtCount = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.user_id', Auth::id())
        ->where('orders.status', 'completed') // Only count products from completed orders
        ->count();

        return
        response()->json(
            ['status' => 'success',
              'data' => [
                'wishlist_count' => $wishlistCount,
                'total_bought_count' =>$totalProductsBoughtCount,
                'wishlist' => $wishlists,
                'total_bought' => $totalProductsBought
              ],

              ]
              , 200);
    }

    public function store(Request $request)
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
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


        $wishlist = wishlist::updateOrCreate(
            ['user_id' => Auth::id(), 'product_id' => $validated['product_id']]
        );

        return response()->json(['status' => 'success', 'data' => $wishlist], 201);
    }

    public function destroy($id)
    {
        $wishlist = wishlist::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        $wishlist->delete();

        return response()->json(['status' => 'success', 'message' => 'Product removed from wishlist successfully.'], 200);
    }
}
