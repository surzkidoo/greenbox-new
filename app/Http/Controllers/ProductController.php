<?php
// app/Http/Controllers/ProductController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\product;
use App\Models\orderItems;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\productCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // Display a listing of products
    public function index(Request $request)
    {
        $sortBy = $request->input('sortBy', 'price'); // Default sort by 'price'
        $sortOrder = $request->input('sortOrder', 'asc'); // Default order 'asc'
        $category = $request->input('category'); // Optional category filter
        $minPrice = $request->input('minPrice'); // Optional minimum price filter
        $maxPrice = $request->input('maxPrice'); // Optional maximum price filter

        // Build the query with optional filters
        $query = product::with(['images', 'user']);


        // Apply category filter if provided
        if (!empty($category)) {
            $query->where('product_categories_id', $category);
        }

        // Apply price range filters if provided
        if ($minPrice !== null && $maxPrice !== null) {
            $query->whereBetween('price', [$minPrice, $maxPrice]);
        } elseif ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        } elseif ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        // Filter by active status
        $query->where('active', true);

        // Apply sorting (with basic validation)
        $allowedSortFields = ['price', 'created_at', 'name'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'price';
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortOrder);

        // Paginate the results (12 items per page)
        $products = $query->paginate(12);




        // Return JSON response
        return response()->json([
            'status' => 'success',
            'data' => ['products'=>$products, // Count all products
            'total_products' => DB::table('products')->count(),
            // Count all items sold (only from completed orders)
            'total_items_sold' => orderItems::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->sum('order_items.item_quantity'),
            'total_quantity_in_carts' => DB::table('cart_items')->sum('quantity')
        ],

        ]);
    }


    public function marketplace(Request $request)
    {
        $sortBy = $request->input('sortBy', 'price'); // Default sort by 'price'
        $sortOrder = $request->input('sortOrder', 'asc'); // Default order 'asc'
        $category = $request->input('category'); // Optional category filter
        $minPrice = $request->input('minPrice'); // Optional minimum price filter
        $maxPrice = $request->input('maxPrice'); // Optional maximum price filter

        // Build the query with optional filters
        $query = product::with(['images', 'user'])
        ->where('active', true) // Filter active products
        ->whereHas('user.vendBusiness', function ($q) {
            $q->where('verify', true); // Ensure vend_business is verified
        });

        // Apply category filter if provided
        if (!empty($category)) {
            $query->where('product_categories_id', $category);
        }

        // Apply price range filters if provided
        if ($minPrice !== null && $maxPrice !== null) {
            $query->whereBetween('price', [$minPrice, $maxPrice]);
        } elseif ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        } elseif ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        // Filter by active status
        $query->where('active', true);

        // Apply sorting (with basic validation)
        $allowedSortFields = ['price', 'created_at', 'name'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'price';
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortOrder);

        // Paginate the results (12 items per page)
        $products = $query->paginate(12);



        // Return JSON response
        return response()->json([
            'status' => 'success',
            'data' => ['products'=>$products, // Count all products
            'total_products' => DB::table('products')->count(),
            // Count all items sold (only from completed orders)
            'total_items_sold' => orderItems::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->sum('order_items.item_quantity'),
            'total_quantity_in_carts' => DB::table('cart_items')->sum('quantity')
        ],

        ]);
    }


    public function greenboxIndex(Request $request)
    {
        $sortBy = $request->input('sortBy', 'price'); // Default sort by 'price'
        $sortOrder = $request->input('sortOrder', 'asc'); // Default order 'asc'
        $category = $request->input('category'); // Optional category filter
        $minPrice = $request->input('minPrice'); // Optional minimum price filter
        $maxPrice = $request->input('maxPrice'); // Optional maximum price filter

        // Fetch all admin IDs from the database
        $adminIds = User::where('role', 'admin')->pluck('id');

        // Build the query for products
        $query = Product::with(['images', 'user'])
            ->whereIn('user_id', $adminIds); // Filter by admin IDs

        // Apply category filter if provided
        if (!empty($category)) {
            $query->where('product_categories_id', $category);
        }

        // Apply price range filters if provided
        if ($minPrice !== null && $maxPrice !== null) {
            $query->whereBetween('price', [$minPrice, $maxPrice]);
        } elseif ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        } elseif ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        // Filter by active status
        $query->where('active', true);

        // Validate sorting field
        $allowedSortFields = ['price', 'created_at', 'name'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'price';
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate the results (12 items per page)
        $products = $query->paginate(12);

        // Aggregated data for all admins
        $totalProducts = product::whereIn('user_id', $adminIds)->count(); // Total products for all admins
        $totalItemsSold = orderItems::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('products.user_id', $adminIds) // Filter by admins' products
            ->where('orders.status', 'completed')
            ->sum('order_items.item_quantity'); // Total items sold for all admins
             $totalQuantityInCarts = DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->whereIn('products.user_id', $adminIds) // Filter by admins' products
            ->sum('cart_items.quantity'); // Total quantity in carts for all admins
            $totalView = product::whereIn('user_id', $adminIds)->sum('view'); // Total products for all admins

        // Return JSON response
        return response()->json([
            'status' => 'success',
            'data' => [
                'products' => $products,
                'total_products' => $totalProducts,
                'total_items_sold' => $totalItemsSold,
                'total_quantity_in_carts' => $totalQuantityInCarts,
                'total_quantity_in_carts' => $totalQuantityInCarts,
                'total_views'=>$totalView

            ],
        ]);
    }

    // Store a newly created product
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            // 'img' => 'required|image|max:2048',
            'price' => 'required|numeric',
            'd_price' => 'nullable|numeric',
            'stock_available' => 'nullable|integer',
            'description' => 'required|string',
            'weight' => 'required|numeric',
            'thumbnail' => 'required|numeric',
            'availability_type' => 'required|in:stock,unlimited',
            'user_id' => 'required|exists:users,id',
            'product_categories_id' => 'required|exists:product_categories,id',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Multiple images
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

        $user = Auth::user();


        if ($user->seller_verified == false) {
            return response()->json([
                'status' => 'error',
                'message' => 'please Register as a vendor to Start Selling!!!',
            ], 400);
        }


        $slug = Str::slug($validated['name']); // Generate a slug from the product name
        $slug = $slug . "-" . Str::random(4);



        $product = Product::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'd_price' => $validated['d_price'] ?? null,
            'stock_available' => $validated['stock_available'],
            'description' => $validated['description'],
            'weight' => $validated['weight'],
            'availability_type' => $validated['availability_type'],
            'user_id' => $validated['user_id'],
            'product_categories_id' => $validated['product_categories_id'],
            'url' => strtolower(str_replace(' ', '-', $validated['name'])) . '-' . uniqid(),
            'active' => true, // Default active status
        ]);


        // Handle multiple images upload
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                if ($image->isValid()) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                    // Move file to public/images/products
                    $image->move(public_path('images/products'), $imageName);

                    // Check if the current index matches the thumbnail index
                    $isThumbnail = ($request->thumbnail == $index);

                    // Store the image path in the product_images table
                    $product->images()->create([
                        'url' => 'images/products/' . $imageName,
                        'thumbnail' => $isThumbnail
                    ]);
                }
            }
        }


        return response()->json(['status'=>'success','message' => 'Product created successfully', 'product' => $product], 201);
    }

    // Show a single product
    public function show($id)
    {
        $product = product::with(['images', 'user', 'category'])->where('id', $id)->first();
        $product->view = $product->view + 1;
        $product->save();

        return response()->json($product);
    }



    public function getProductByUser($userId)
    {
        // Fetch products with their associated images
        $products = product::where('user_id', $userId)
            ->with(['images', 'user', 'category']) // Include images relationship
            ->get();

        // Check if products are found
        if ($products->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No products found for this user'], 404);
        }

        // Return the products with images
        return response()->json([
            'status' => 'success',
            'data' => $products,
        ], 200);
    }



    public function showBySlug($slug)
    {
        // Find the product by slug
        $product = product::where('url', $slug)->with(['images', 'user', 'category'])->first();

        // Check if the product exists
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        // Return the product details
        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    // Update an existing product
    public function update(Request $request, $id)
    {
        // Fetch the product to be updated
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'd_price' => 'nullable|numeric',
            'stock_available' => 'nullable|integer',
            'description' => 'required|string',
            'weight' => 'required|numeric',
            'thumbnail' => 'required|numeric',
            'availability_type' => 'required|in:stock,unlimited',
            'product_categories_id' => 'required|exists:product_categories,id',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Multiple images
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


        // Update the product details
        $product->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'd_price' => $validated['d_price'],
            'stock_available' => $validated['stock_available'],
            'description' => $validated['description'],
            'weight' => $validated['weight'],
            'availability_type' => $validated['availability_type'],
            'product_categories_id' => $validated['product_categories_id'],
            // Optional: Update the product's URL if the name changes
            'url' => strtolower(str_replace(' ', '-', $validated['name'])) . '-' . uniqid(),
        ]);



        // Handle updating images (if any)
        if ($request->hasFile('images')) {
            $product->images()->get()->each(function ($image) {
                Storage::delete(public_path($image->url));
                $image->delete();
            });

            foreach ($request->file('images') as $index => $image) {
                if ($image->isValid()) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                    // Move file to public/images/products
                    $image->move(public_path('images/products'), $imageName);

                     // Check if the current index matches the thumbnail index
                     $isThumbnail = ($request->thumbnail == $index);

                     // Store the image path in the product_images table
                     $product->images()->create([
                         'url' => 'images/products/' . $imageName,
                         'thumbnail' => $isThumbnail
                     ]);
                }
            }
        }





        return response()->json(['status'=>'success','message' => 'Product updated successfully', 'product' => $product], 200);
    }


    // Delete a product
    public function destroy($id)
    {
        $product = Product::with('images')->findOrFail($id);

        // Delete the image files from the server before deleting the image records
        foreach ($product->images as $image) {
            $imagePath = public_path($image->url);  // Assuming 'url' stores the image path
            if (file_exists($imagePath)) {
                unlink($imagePath);  // Delete the file from the server
            }
        }

        // Delete the associated images from the database
        $product->images()->delete();

        // Now delete the product record itself
        $product->delete();



        return response()->json(['status'=>'success','message' => 'Product deleted successfully']);
    }

    public function deactive($id)
    {
        $product = product::findOrFail($id);
        $product->active = false;
        $product->save();

        return response()->json(['status'=>'success','message' => 'Product Deactivated successfully']);
    }


    public function active($id)
    {
        $product = product::findOrFail($id);
        $product->active = true;
        $product->save();

        return response()->json(['status'=>'success','message' => 'Product Activated successfully']);
    }



    public function storeProductCategory(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
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

        $category = productCategory::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }


    public function getProductCategory()
    {
        $categories = ProductCategory::all();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    public function updateProductCategory(Request $request, $id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Category not found'], 404);
        }

        $category->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Category Updated successfully',
        ]);
    }


    public function destroyProductCategory($id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json(['status'=>'success','status' => 'error', 'message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully',
        ]);
    }
}
