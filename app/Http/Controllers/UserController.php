<?php

namespace App\Http\Controllers;

use App\Models\cart;
use App\Models\User;
use App\Models\order;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{


    public function getUsers(Request $request)
    {

        $accountStatus = $request->input('account_status'); // e.g., 'active', 'inactive'

        // Build the query with optional filters
        $query = User::query();


        if (!is_null($accountStatus)) {
            $query->where('account_status', $accountStatus);
        }


        $query->where('role', 'user'); // Filter only users

        // Paginate results for better performance
        $users = $query->paginate(20);

        // Return a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    // Get user details by ID or the authenticated user
    public function getUser($userId = null): JsonResponse
    {
        $user = $userId ? User::find($userId) : Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        return response()->json(['status' => 'success', 'user' => $user], 200);
    }

    public function getProductByUser($userId)
    {
        // Fetch products with their associated images
        $products = product::where('user_id', $userId)
                    ->with(['images','user','category']) // Include images relationship
                    ->get();

        // Check if products are found
        if ($products->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No products found for this user'], 404);
        }

        // Return the products with images
        return response()->json([
            'status' => 'success',
            'products' => $products,
        ], 200);
    }

    // Get carts associated with a specific user
    public function getCartByUser($userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        $carts = cart::where('user_id', $userId)->get();

        return response()->json(['status' => 'success', 'carts' => $carts->load('items')], 200);
    }

    // Get orders associated with a specific user
    public function getOrderByUser(Request $request , $userId): JsonResponse
    {

        $statuses = $request->query('status', ['on_delivery', 'delivered']);

        // Ensure statuses are always an array
        $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);

        // Validate the statuses to ensure they match allowed values
        // $request->validate([
        //     'status' => 'array',
        //     'status.*' => 'in:pending,completed,on_delivery,delivered,canceled',
        // ]);



         // Total Paid Invoices
         $totalDelivered = DB::table('orders')
             ->whereIn('status', ['delivered'])->where('user_id',$userId)
             ->count();


             $totalCancelled = DB::table('orders')
             ->whereIn('status', ['cancelled'])->where('user_id',$userId)
             ->count();


             $totalProductsBought = DB::table('order_items')
             ->join('orders', 'order_items.order_id', '=', 'orders.id')
             ->where('orders.user_id', $userId)
             ->where('orders.status', 'completed') // Only count products from completed orders
             ->sum('order_items.item_quantity');


        // Query orders based on user ID and the provided statuses
        $orders = order::where('user_id', $userId)
            ->whereIn('status', $statuses)
            ->with(['items.product.user','items.product.user.vendBusiness','items.shipping','billingAddress', 'shippingAddress', 'payment'])
            ->paginate(10); // Adjust the pagination count as needed

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_delivered'=>$totalDelivered,
                'total_cancelled'=>$totalCancelled,
                'total_products_bought' => $totalProductsBought,
                'orders'=>$orders
            ],
        ]);

    }


    //   public function getStore(Request $request , $userId): JsonResponse
    // {


    //     $productCount = product::where('user_id', $userId)->where('active',true)
    //     ->count();

    //     $totalItemsSold = Order::whereHas('items', function ($query) use ($userId) {
    //         $query->whereHas('product', function ($subQuery) use ($userId) {
    //             $subQuery->where('user_id', $userId); // Filter products owned by the user
    //         });
    //     })
    //     ->where('status', 'delivered') // Only count completed orders
    //     ->withSum('items as total_quantity', 'quantity') // Sum quantities of sold items
    //     ->get()
    //     ->sum('total_quantity');


    //     $totalSalesForVendor = DB::table('order_items')
    //         ->join('products', 'order_items.product_id', '=', 'products.id')
    //         ->join('orders', 'order_items.order_id', '=', 'orders.id')
    //         ->where('products.user_id', $userId) // Filter products owned by the vendor
    //         ->where('orders.status', 'delivered') // Only include completed orders
    //         ->sum(DB::raw('order_items.quantity * order_items.price'));



    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'sales'=>$totalSalesForVendor,
    //             'sold'=>$totalItemsSold,
    //             'products' => $productCount,
    //             'orders'=>$orders
    //         ],
    //     ]);

    // }


}


