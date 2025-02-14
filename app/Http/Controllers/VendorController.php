<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\order;
use App\Models\product;
use App\Models\vendBank;
use App\Models\orderItems;
use App\Models\vendProduct;
use App\Models\vendBusiness;
use Illuminate\Http\Request;
use App\Models\vendorsettings;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Mail\Templete;
use App\Mail\VendorVerificationMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class VendorController extends Controller
{
        // Create new records for bio, farm, bank, guarantor, and next of kin
        public function createVendorRecord(Request $request)
        {
            $validationRules = [
                // Business fields
                'name' => 'required|string|max:255',
                'reg_no' => 'required|string|max:255',
                'contact_name' => 'required|string|max:255',
                'email' => 'required|email|unique:vend_businesses',
                'phone' => 'required|string|max:20',
                'state' => 'required|string',
                'lga' => 'required|string',
                'office_address' => 'nullable|string',
                'website' => 'nullable|url',
                'social' => 'nullable|string',
                'id_type' => 'nullable|string',
                'id_value' => 'nullable|string',
                'tin' => 'nullable|string',
                'vat_number' => 'nullable|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

                // Product fields
                'categories' => 'required|string',
                'shipping' => 'required|in:independently,hib_logistic',
                'return_policy' => 'required|string',
                'shipping_zone' => 'nullable|string',
                // 'shipping_zone.*' => 'string|in:abia,adamawa,akwa ibom,anambra,bauchi,bayelsa,benue,borno,cross river,delta,ebonyi,edo,ekiti,enugu,fCT,gombe,imo,jigawa,kaduna,kano,katsina,kebbi,kogi,kwara,lagos,nasarawa,niger,ogun,ondo,osun,oyo,plateau,rivers,sokoto,taraba,yobe,zamfara',

                // Bank fields
                'bank_name' => 'required|string|max:255',
                'bank_account' => 'required|string|max:255',
                'payment_method' => 'required|string|max:255',
                'commission' => 'required|numeric',
                'swift_code' => 'required|string|max:50',
                'iban' => 'nullable|string|max:255',
                'bank_doc' => 'nullable|string|max:255',
                'tin_doc' => 'nullable|string|max:255',
                'pricing' => 'nullable|string|max:255',

            ];

            // Validate the request
            $validated = $request->validate($validationRules);

            if ($request->hasFile('logo')) {
                $image = $request->file('logo');
                $imageName = time().'.'.$image->getClientOriginalExtension();
                $image->move(public_path('images/vendor/logo'), $imageName);
            }



            DB::beginTransaction();

            try {
                // Create each record separately
                $business = vendBusiness::create([
                    'name' => $validated['name'],
                    'reg_no' => $validated['reg_no'],
                    'contact_name' => $validated['contact_name'],
                    'email' => $validated['email'],
                    'state' => $validated['state'],
                    'lga' => $validated['lga'],
                    'phone' => $validated['phone'],
                    'office_address' => $validated['office_address'],
                    'website' => $validated['website'],
                    'social' => $validated['social'],
                    'id_type' =>  $validated['id_type'],
                    'id_value' => $validated['id_value'],
                    'tin' => $validated['tin'],
                    'vat_number' => $validated['vat_number'],
                    'logo' => $imageName ?? null,
                    'user_id' => Auth::id()
                ]);

                $product = vendProduct::create([
                    'categories' => $validated['categories'],
                    // 'description' => $validated['description'],
                    // 'inventory' => $validated['inventory'],
                    'shipping' => $validated['shipping'],
                    'shipping_zone' => $validated['shipping_zone'],
                    'return_policy' => $validated['return_policy'],
                    'user_id' => Auth::id(),
                ]);

                $bank = vendBank::create([
                    'bank_name' => $validated['bank_name'],
                    'bank_account' => $validated['bank_account'],
                    'payment_method' => $validated['payment_method'],
                    'commission' => $validated['commission'],
                    'swift_code' => $validated['swift_code'],
                    'iban' => $validated['iban'],
                    'user_id' => Auth::id(),

                ]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'All records created successfully.',
                    'data'=>[
                        'business' =>$business,
                        'product' => $product,
                        'bank' => $bank,
                    ]


                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to create records. Please try again.'.$e], 500);
            }
        }

            // Existing createVendorRecord method...

    public function getByUserId($userId)
    {

        $businessRecords = vendBusiness::where('user_id', $userId)->get();
        $prodRecords = vendProduct::where('user_id', $userId)->get();
        $banksRecords = vendBank::where('user_id', $userId)->get();

        $productCount = product::where('user_id', $userId)->where('active',true)
        ->count();

        $totalItemsSold = order::whereHas('items', function ($query) use ($userId) {
            $query->whereHas('product', function ($subQuery) use ($userId) {
                $subQuery->where('user_id', $userId); // Filter products owned by the user
            });
        })
        ->where('status', 'delivered') // Only count completed orders
        ->withSum('items as total_quantity', 'item_quantity') // Sum quantities of sold items
        ->get()
        ->sum('total_quantity');


        $totalSalesForVendor = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('products.user_id', $userId) // Filter products owned by the vendor
            ->where('orders.status', 'delivered') // Only include completed orders
            ->sum(DB::raw('order_items.item_quantity * order_items.price'));


        return response()->json([
            'status' => 'success',
            'sales'=>$totalSalesForVendor,
            'sold'=>$totalItemsSold,
            'products' => $productCount,
            'data' => [
                $businessRecords,
                $prodRecords,
                $banksRecords
            ]
        ]);
    }


    public function getAllVendors(Request $request)
    {
        $value  = $request->query('status','active');

        $value = $value=='active'? true : false;


        $users = User::whereHas('vendBusiness', function($query) use ($value) {
            $query->where('verify', $value);  // Filter vendBusiness based on 'verify' field
        })
        ->with(['vendBusiness', 'vendProduct', 'vendBank'])  // Eager load vendBusiness, vendProduct, and vendBank
        ->paginate(5);

           $vendors = $users->map(function ($user) {
            $no_products = product::where('user_id',$user->id)->count();
            $sold_product_quantity = orderItems::whereHas('product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['completed', 'on_delivery', 'delivered']);  // Filter by multiple statuses
            })
            ->sum('item_quantity');  // Assuming the quantity of each product sold is stored in 'quantity'

            return [
                'user' => $user,
                'num_of_product' => $no_products,
                'num_of_sold_product' => $sold_product_quantity,
                'business' => $user->vendBusiness,
                'product' => $user->vendProduct,
                 'bank' => $user->vendBank,

            ];
        });


        return response()->json([
            'status' => 'success',
            'message' => 'All vendors retrieved successfully.',
            'data' => ['vendors'=>$vendors,
            'total_products' => DB::table('products')
            ->whereNotIn('user_id', function ($query) {
                $query->select('id')->from('users')->where('role', 'admin');
            })
            ->count(),
            'total_vendor' => User::whereHas('vendBusiness')->count(),
            'vendor_pending' =>  User::whereHas('vendBusiness', function($query) use ($value) {
                $query->where('verify', false);  // Filter vendBusiness based on 'verify' field
            })->count()
        ],
        ], 200);
    }


    public function updateVendorRecord(Request $request, $userId)
    {
        $validationRules = [
            // Business fields
            'name' => 'nullable|string|max:255',
            'reg_no' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:vend_businesses,email,' . $userId . ',user_id',
            'phone' => 'nullable|string|max:20',
            'state' => 'nullable|string',
            'lga' => 'nullable|string',
            'office_address' => 'nullable|string',
            'website' => 'nullable|url',
            'id_type' => 'nullable|string',
            'id' => 'nullable|string',
            'tin' => 'nullable|string',
            'vat_number' => 'nullable|string',
            'logo' => 'nullable|image|max:2048', // Updated for file upload validation
            'verify' => 'boolean',

            // Product fields
            'categories' => 'nullable|string',
            'description' => 'nullable|boolean',
            'shipping' => 'nullable|in:independently,hib_logistic',
            'return_policy' => 'nullable|boolean',
            'shipping_zone' => 'nullable|array',
            'shipping_zone.*' => 'string|in:abia,adamawa,akwa ibom,anambra,bauchi,bayelsa,benue,borno,cross river,delta,ebonyi,edo,ekiti,enugu,fCT,gombe,imo,jigawa,kaduna,kano,katsina,kebbi,kogi,kwara,lagos,nasarawa,niger,ogun,ondo,osun,oyo,plateau,rivers,sokoto,taraba,yobe,zamfara',

            // Bank fields
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
            'commission' => 'nullable|numeric|min:0|max:100',
            'swift_code' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:255',
            'bank_doc' => 'nullable|string|max:255',
            'tin_doc' => 'nullable|string|max:255',
            'pricing' => 'nullable|string|max:255',
        ];

        $validated = $request->validate($validationRules);

        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/vendor/logo'), $imageName);
            $validated['logo'] = 'images/vendor/logo/' . $imageName;
        }

        DB::beginTransaction();

        try {
            // Update business
            $business = vendBusiness::where('user_id', $userId)->firstOrFail();
            $business->update(array_filter($validated));

            // Update product
            $product = vendProduct::where('user_id', $userId)->firstOrFail();
            $productData = array_filter($validated, function ($key) {
                return in_array($key, ['categories', 'description', 'shipping', 'return_policy', 'shipping_zone']);
            }, ARRAY_FILTER_USE_KEY);
            $product->update($productData);

            // Update bank
            $bank = vendBank::where('user_id', $userId)->firstOrFail();
            $bankData = array_filter($validated, function ($key) {
                return in_array($key, ['bank_name', 'bank_account', 'payment_method', 'commission', 'swift_code', 'iban', 'bank_doc', 'tin_doc', 'pricing']);
            }, ARRAY_FILTER_USE_KEY);
            $bank->update($bankData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'All records updated successfully.',
                'data' => [
                    'business' => $business,
                    'product' => $product,
                    'bank' => $bank,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update records. Please try again.'], 500);
        }
    }



    public function deleteVendorRecord($id)
    {
        DB::beginTransaction();

        try {
            $userId = Auth::id();

            vendBusiness::where('user_id', $userId)->delete();
            vendProduct::where('user_id', $userId)->delete();
            vendBank::where('user_id', $userId)->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Records deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to delete records. Please try again.'], 500);
        }
    }

    public function activateVendor($userId)
    {

        try {
            $business = vendBusiness::where('user_id', $userId)->firstOrFail();
            $business->verify = true;
            $business->save();

            $user = User::where('id',$userId)->first();
            $user->seller_verified = true;
            $user->save();


            Mail::to($user->email)->send(new Templete(
                'Your vendor registration has been approved!',
                'Welcome to HiBGreenbox',
                'Vendor Registration Approved',
                'Thank you for choosing us!',
                'Start Shipping Now',
                'https://app.hibgreenbox.com'
            ));


            vendorsettings::create([
                'user_id'=>$user->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Vendor record activated successfully.',
                'data' => $business
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to activate vendor record. Please try again.'.$e], 500);
        }
    }


    public function deactivateVendor($userId)
    {


        try {
            $business = vendBusiness::where('user_id', $userId)->firstOrFail();
            $business->verify = false;
            $business->save();

            $user = User::where('id',$userId)->first();
            $user->seller_verified = false;
            $user->save();


            Mail::to($user->email)->send(new Templete(
                'Your vendor Account has been Disabled!, please contact support for more info on why your account was disabled',
                'Account Disabled',
                'Vendor Account Disabled',
                'Thank you for choosing us!',
                'Contact Support',
                'https://greenbox.com/contact'
            ));

            return response()->json([
                'status' => 'success',
                'message' => 'Vendor record deactivated successfully.',
                'data' => $business
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to deactivate vendor record. Please try again.'], 500);
        }
    }

    public function getVendorSetting($userId){

    $vendorSetting = vendorsettings::where('user_id', $userId)->first();


    if (!$vendorSetting) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vendor setting not found.'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data' => $vendorSetting
    ], 200);
}


    public function updateVendorSetting(Request $request,$userId)
{

    $vendorSetting = vendorsettings::where('user_id', $userId)->first();

    if (!$vendorSetting) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vendor setting not found.'
        ], 404);
    }

    $validated = $request->validate([
        'prefer_Currency' => 'string|max:255',
        'prefer_language' => 'string|max:255',
        'primary_country' => 'string|max:255',
        'time_zone' => 'nullable|string|max:255',
        'email_notification' => 'boolean',
        'ride_notification' => 'boolean',
        'reminders' => 'boolean',
        'promotion_notification' => 'boolean',
        'Bell_notification' => 'boolean',
        'popup_notification' => 'boolean',
        'browser_notification' => 'boolean',
    ]);

    $vendorSetting->update($validated);

    return response()->json([
        'status' => 'success',
        'message' => 'Vendor setting updated successfully.',
        'data' => $vendorSetting
    ], 200);
}


}
