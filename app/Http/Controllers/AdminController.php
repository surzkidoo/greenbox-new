<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\Templete;
use App\Models\orderItems;
use App\Models\permission;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminController extends Controller
{

    public function getshipping()
    {


        // Get the last 12 months
        $startDate = Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Get shipments grouped by month
        $monthlyShipments = DB::table('shippings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total_shipments')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill in missing months with 0 shipments
        $chartData = [];
        $period = Carbon::now()->subMonths(11)->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $month = $period->format('Y-m');
            $chartData[] = [
                'month' => $month,
                'total_shipments' => $monthlyShipments->get($month)->total_shipments ?? 0
            ];
            $period->addMonth();
        }

        return $chartData;
    }


    public function getUserByMonth()
    {
        $startDate = Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Fetch user registrations grouped by month
        $monthlyRegistrations = DB::table('users')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total_registrations')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Ensure all months are accounted for
        $chartData = [];
        $period = Carbon::now()->subMonths(11)->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $month = $period->format('Y-m');
            $chartData[] = [
                'month' => $month,
                'total_registrations' => $monthlyRegistrations->get($month)->total_registrations ?? 0
            ];
            $period->addMonth();
        }

        return $chartData;
    }

    public function revWeek()
    {
        // Get the start and end date for the current and previous week
        $currentWeekStart = Carbon::now()->startOfWeek();
        $currentWeekEnd = Carbon::now()->endOfWeek();

        $previousWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $previousWeekEnd = Carbon::now()->subWeek()->endOfWeek();

        // Fetch revenue for the current and previous week
        $weeklyRevenue = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$previousWeekStart, $currentWeekEnd]) // Range between the two weeks
            ->select(
                DB::raw('WEEK(orders.created_at) as week_number'),
                DB::raw('YEAR(orders.created_at) as year'),
                DB::raw('SUM(order_items.admin_commission) as total_revenue')
            )
            ->groupBy(DB::raw('YEAR(orders.created_at), WEEK(orders.created_at)'))
            ->orderBy('year')
            ->orderBy('week_number')
            ->get();

        // Separate current and previous week data
        $currentWeekRevenue = $weeklyRevenue->firstWhere('week_number', Carbon::now()->weekOfYear);
        $previousWeekRevenue = $weeklyRevenue->firstWhere('week_number', Carbon::now()->subWeek()->weekOfYear);

        return [
            'current_week_revenue' => $currentWeekRevenue ? $currentWeekRevenue->total_revenue : 0,
            'previous_week_revenue' => $previousWeekRevenue ? $previousWeekRevenue->total_revenue : 0
        ];
    }


    public function summary()
    {

        $data = [
            // Count all users
            'total_users' => DB::table('users')->count(),

            // Count all products
            'total_products' => DB::table('products')->count(),

            // Count all items sold (only from completed orders)
            'total_items_sold' => orderItems::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->sum('order_items.quantity'),

            // Count all completed orders
            'total_completed_orders' => DB::table('orders')->where('status', 'completed')->count(),

            // Sum admin commission from completed orders
            'total_admin_commission' => DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->sum('order_items.admin_commission'),

            'total_admin_insurance' => DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->sum('order_items.insurance'),

            'total_active_farms' => DB::table('farms')
                ->where('is_active', true)
                ->count(),

            'monthly_revenue' => DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->whereBetween('orders.created_at', [
                    Carbon::now()->subMonths(12)->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])
                ->select(
                    DB::raw('DATE_FORMAT(orders.created_at, "%Y-%m") as month'),
                    DB::raw('SUM(order_items.admin_commission) as total_revenue')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get(),


            'monthly_revenue_by_location' => DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('addresses', 'orders.user_id', '=', 'addresses.user_id')  // Join to the addresses table
                ->where('orders.status', 'completed')
                ->whereBetween('orders.created_at', [
                    Carbon::now()->subMonths(12)->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])
                ->select(
                    DB::raw('DATE_FORMAT(orders.created_at, "%Y-%m") as month'),
                    'addresses.city',  // Add the city column for grouping
                    DB::raw('SUM(order_items.admin_commission) as total_revenue')
                )
                ->groupBy('month', 'addresses.city')  // Group by both month and city
                ->orderBy('month')
                ->get(),

            'monthly_shippping' => $this->getshipping(),
            'monthly_user' => $this->getUserByMonth(),
            'revenue_by_current_previous_Week' => $this->revWeek(),
        ];

        response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }


    public function addAdminUser(Request $request)
    {

        $rules = [
            'access_type' => 'required|string',
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


        $checkmail = User::where('email', $request->email)->first();

        if (!$checkmail) {
            response()->json([
                'status' => 'error',
                'message' => 'User Exist with that email',
            ], 422);
        }

        // Create the new user with validated data
        $user = User::create([
            'firstname' => $request->username,
            'lastname' => 'admin',
            'phone' => 'admin',
            'email' => $request->email,
            'address' => 'admin',
            'occupation' =>  'admin',
            'state' => 'kebbi',
            'lga' => 'birnin kebbi',
            'gender' => 'admin',
            'role' => 'admin',
            'refer_by' => '001',
            'password' => Hash::make($request->password),
            'account_status' => 'active',
            'access_type' =>   $request->access_type,
            'email_verified' => true,
        ]);



        // Generate a unique referral code using user ID and random string
        $referralCode = strtoupper(Str::random(5)) . $user->id;
        // Save the referral code in the database, for example, in the `users` table
        $user->referral_code = $referralCode;

        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
            $user->avatar = 'images/' . $imageName;

        }

        $user->save();

        Mail::to($request->email)->send(new Templete(
            'Your Admin account is created! email : '.$request->email . 'password'. $request->password,
            'Welcome to HiBGreenbox',
            'Account Created',
            'Thank you for choosing us!',
            'Get Started',
            'https://app.hibgreenbox.com'
        ));

        $access = explode(',', $request->access_type);
        $permissions = permission::whereIn('access_type', $access)->pluck('id');
        $user->permissions()->sync($permissions);

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'New Admin Added',
            'user' => $user
        ], 201);
    }

    public function getAdminUsers()
    {
        $adminUsers = User::with('permissions')->where('role', 'admin')->get();

        if ($adminUsers->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No admin users found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $adminUsers
        ], 200);
    }

    public function editAdminPermission(Request $request, $userId)
    {
        $rules = [
            'default' => 'required|boolean',
            'permissions' => 'nullable|string',
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

        try {
            // Find the user by ID
            $user = User::findOrFail($userId);

            // Check if the user is an admin
            if ($user->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The specified user is not an admin.',
                ], 403);
            }

            if ($request->default) {
                // Update to default permissions based on user's access type
                $accessTypes = explode(',', $user->access_type);
                $permissions = permission::whereIn('access_type', $accessTypes)->pluck('id');

                // Sync the default permissions for the admin
                $user->permissions()->sync($permissions);
            } else {
                // Update with provided custom permissions
                if (!$request->permissions) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Permissions must be provided when "default" is set to false.',
                    ], 422);
                }

                $permissions = explode(',', $request->permissions);
                $user->permissions()->sync($permissions);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Admin permissions updated successfully.',
                'user' => $user,
                'permissions' => $user->permissions,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin user not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update admin permissions. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


        public function startUpData(){

            Permission::create([
                'name' => 'greenbox management',
                'access_type' => 'greenbox',
                'role_for' => 'admin',
            ]);

            Permission::create([
                'name' => 'fis management',
                'access_type' => 'fis',
                'role_for' => 'admin',
            ]);

            Permission::create([
                'name' => 'delete_posts',
                'access_type' => 'chats',
                'role_for' => 'admin',
            ]);


            Permission::create([
                'name' => 'support',
                'access_type' => 'support',
                'role_for' => 'admin',
            ]);


            Permission::create([
                'name' => 'mails',
                'access_type' => 'mails',
                'role_for' => 'admin',
            ]);

            Permission::create([
                'name' => 'updates',
                'access_type' => 'updates',
                'role_for' => 'admin',
            ]);


            Permission::create([
                'name' => 'user mangement',
                'access_type' => 'management',
                'role_for' => 'admin',
            ]);

            Permission::create([
                'name' => 'Farm Management',
                'role_for' => 'user',
            ]);

            Permission::create([
                'name' => 'HiB greenpay',
                'role_for' => 'user',
            ]);

            Permission::create([
                'name' => 'Verified Account',
                'role_for' => 'user',
            ]);

            Permission::create([
                'name' => 'Manage Sales',
                'role_for' => 'user',
            ]);

            Permission::create([
                'name' => 'Track Profits and Analytics',
                'role_for' => 'user',
            ]);

            Permission::create([
                'name' => 'Accesibility and to HiB logistics',
                'role_for' => 'user',
            ]);

            $user = User::where('id','1')->first();
            $user->permissions()->attach([1,2,3,4,5,6]);

        }


}
