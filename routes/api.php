<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FisController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FarmsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\GenericController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\FarmTaskController;
use App\Http\Controllers\FarmTypeController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\FarmTypeControlller;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LogisticBioController;
use App\Http\Controllers\FarmActivityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FarmInventoryController;
use App\Http\Controllers\LogMessageController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\SubscriptionUserController;
use App\Models\shipping;
use App\Models\subscriptionPlan;

//Authentication & Recovery
Route::get('/', function(){
    return "API Working";
}); // Register user //tested

Route::post('/register', [AuthController::class, 'register']); // Register user //tested
Route::post('/login', [AuthController::class, 'login']);// Login user //tested
Route::get('/email/verify/{token}', [AuthController::class, 'verifyEmail'])->name('verify.email'); //tested
//Reset Password
Route::post('password/email', [AuthController::class, 'sendResetLinkEmail']); //tested
Route::post('password/reset', [AuthController::class, 'resetPassword']); //tested

//Public Blog Route
Route::get('/blogs', [BlogController::class, 'index']); // Get all blogs //tested
Route::get('/blogs/{slug}', [BlogController::class, 'show']); // Get a single blog by slug //tested

//Product
Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']); //tested


//Generic Route
Route::get('/states', [GenericController::class, 'getStates']);
Route::get('/states/{state}/lga', [GenericController::class, 'getLgasByState']);




 Route::get('/products/marketplace', [ProductController::class, 'marketplace']); //tested

 Route::get('/admin/startup', [AdminController::class, 'startUpData']); //tested

 Route::post('/paystack/webhook', [GenericController::class, 'handleWebhook']);


Route::middleware('auth:sanctum')->group(function () {

     //Cart System
 Route::get('/cart', [CartController::class, 'viewCart']); //tested
 Route::post('/cart', [CartController::class, 'addToCart']); //tested
 Route::put('/cart-item/{cartItemId}', [CartController::class, 'updateCartItem']); //tested
 Route::delete('/cart-item/{cartItemId}', [CartController::class, 'removeCartItem']); //tested

    Route::get('/users', [UserController::class, 'getUsers']); // Add appropriate middleware //tested
    Route::post('/user/{userId}/update', [UserController::class, 'updateUserInfo']); //tested
    // Route::get('/user', [UserController::class, 'getUser']); //tested
    Route::get('/user/{userId}', [UserController::class, 'getUser']); //tested
    Route::get('/user/{userId}/products', [UserController::class, 'getProductByUser']); //tested
    // Route::get('/user/{userId}/carts', [UserController::class, 'getCartByUser']);
    Route::get('/user/{userId}/orders', [UserController::class, 'getOrderByUser']); //tested
    // Route::get('/user/{userId}/store', [UserController::class, 'getStore']);


    //Blog System
    Route::post('/blogs', [BlogController::class, 'store']); // Create a new blog //tested
    Route::delete('/blogs/{slug}', [BlogController::class, 'destroy']); // Delete a blog by slug //tested
    Route::patch('/blogs/update/{Id}', [BlogController::class, 'update']); // Update a blog by slug //tested


    //Product Category Management
    Route::post('/category/product/', [ProductController::class, 'storeProductCategory']); //tested
    Route::get('/category/product', [ProductController::class, 'getProductCategory']); //tested
    Route::patch('/category/product/{id}', [ProductController::class, 'updateProductCategory']); //tested
    Route::delete('/category/product/{id}', [ProductController::class, 'destroyProductCategory']); //tested

    //Product Management
    // Route::apiResource('products', ProductController::class);
    Route::get('/products', [ProductController::class, 'index']); //tested
    Route::get('/products/greenbox', [ProductController::class, 'greenboxIndex']); //tested
    Route::get('/products/{id}', [ProductController::class, 'show']); //tested
    Route::post('/products', [ProductController::class, 'store']); //tested
    Route::put('/products/{id}', [ProductController::class, 'update']); //tested
    Route::post('/products/activate/{id}', [ProductController::class, 'active']); //tested
    Route::post('/products/deactivate/{id}', [ProductController::class, 'deactive']); //tested
    Route::delete('/products/{id}', [ProductController::class, 'destroy']); //tested


    //Coupon
    Route::post('/coupon', [CouponController::class, 'store']); // Create a coupon //tested
    Route::get('/coupon', [CouponController::class, 'index']); // List all coupons //tested
    Route::get('/coupon/{id}', [CouponController::class, 'show']); // Show a single coupon //tested
    Route::put('/coupon/{id}', [CouponController::class, 'update']); // Update a coupon //tested
    Route::delete('/coupon/{id}', [CouponController::class, 'destroy']); // Delete a coupon //tested
    Route::post('/coupon/calculate-discount', [CouponController::class, 'calculateDiscount']); //tested
    Route::post('/discount/add', [CouponController::class, 'assignDiscountsToProducts']); //tested



    //Farmer Verification System
    Route::post('/fis/create', [FisController::class, 'createFisRecord']); //tested
    Route::get('/fis/active/{id}', [FisController::class, 'activateFarmer']); //tested
    Route::post('/fis/deactive/{id}', [FisController::class, 'deactivateFarmer']);//tested
    Route::post('/fis/reject/{id}', [FisController::class, 'rejectPending']);
    Route::get('/fis/user/{id}', [FisController::class, 'getFisByUserId']); //tested
    Route::get('/fis', [FisController::class, 'getAllFisRecords']); //tested
    Route::put('/fis/update/{id}', [FisController::class, 'updateFisRecord']); //
    Route::delete('/fis/delete/{id}', [FisController::class, 'deleteFisRecord']);

    //Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']); // Get user's wishlist //tested
    Route::post('/wishlist', [WishlistController::class, 'store']); // Add product to wishlist //tested
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']); //tested


    //User's Permissons
    Route::get('/permissions', [PermissionController::class, 'index']); // Get all permissions //tested
    Route::post('/permissions', [PermissionController::class, 'store']); // Create a new permission //tested
    Route::get('/permissions/admin', [PermissionController::class, 'getAdminPermissions']); //tested
    Route::get('/permissions/user', [PermissionController::class, 'getUserPermissions']); //tested
    Route::get('/permissions/{id}', [PermissionController::class, 'show']); // Get a specific permission //tested
    Route::put('/permissions/{id}', [PermissionController::class, 'update']); // Update a specific permission //tested
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']); //Delete permission //tested

    Route::post('/users/{userId}/permissions/assign', [PermissionController::class, 'assignPermission']); // Assign permission //tested
    Route::delete('/users/{userId}/permissions/revoke', [PermissionController::class, 'revokePermission']); //tested


    //Notification Route
    Route::post('/notifications', [NotificationController::class, 'create']);
    Route::get('/notifications', [NotificationController::class, 'index']); //tested
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']); //tested
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    //User Settins
    Route::get('/settings', [SettingController::class, 'index']); // Get user settings //no need
    Route::post('/settings', [SettingController::class, 'store']); // Create or update user settings //no need
    Route::get('/settings/{id}', [SettingController::class, 'show']); // Get settings by user ID  //tested
    Route::put('/settings/{id}', [SettingController::class, 'update']); // Update settings by user ID //tested
    Route::delete('/settings/{id}', [SettingController::class, 'destroy']);


    Route::get('/addresses', [AddressController::class, 'show']); // Read all
    Route::post('/addresses', [AddressController::class, 'store']); // Create //tested
    Route::get('/addresses/{id}', [AddressController::class, 'show']); // Read specific //tested
    Route::put('/addresses/{id}', [AddressController::class, 'update']); // Update //tested
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']); // Delete

    //Message Route
    Route::post('/messages', [MessageController::class, 'sendMessage']); //tested
    Route::get('/contacts', [MessageController::class, 'getActiveContacts']); //tested
    Route::get('/messages/search-contacts', [MessageController::class, 'searchContacts']); //tested
    Route::get('/messages/{userId}', [MessageController::class, 'getMessages']); //tested


  //Order Route
    Route::get('/order', [OrderController::class, 'listOrders']); //tetsed
    Route::get('/order/invoice', [OrderController::class, 'invoiceOrders']); //
    Route::post('/order/checkout', [OrderController::class, 'checkout']); //tested
    Route::post('/order/add-shipping', [OrderController::class, 'getShipping']); //tested
    Route::post('/order/placement', [OrderController::class, 'placeOrder']); //tested
    Route::get('/order/{id}', [OrderController::class, 'getOrder']); // Get order details by ID //tested
    Route::get('/order/{id}/approve-transfer-payment', [OrderController::class, 'approvePayment']); // Get order details by ID //tested
    //admin change order status before shipping
    Route::post('/order/{id}/change-status', [shipping::class, 'changeStatusorder']); // Change order status

    //Vendor Seller
    Route::get('/vendor/{userId}/orders', [OrderController::class, 'getOrdersByvendor']); // Get orders for Vendor  //half-test
    Route::post('/vendor', [VendorController::class, 'createVendorRecord']);//tested
    Route::get('/vendor', [VendorController::class, 'getAllVendors']);//tested
    Route::put('/vendor/{userId}', [VendorController::class, 'updateVendorRecord']);//tested
    Route::get('/vendor/{userId}', [VendorController::class, 'getByUserId']);//tested
    Route::delete('/vendor/{userId}', [VendorController::class, 'deleteVendorRecord']);//tested
    Route::post('/vendor/{userId}/activate', [VendorController::class, 'activateVendor']);//tested
    Route::post('/vendor/{userId}/deactivate', [VendorController::class, 'deactivateVendor']);//tested
    Route::get('/vendorsetting/{userId}', [VendorController::class, 'getVendorSetting']);//tested
    Route::put('/vendorsetting/{userId}', [VendorController::class, 'updateVendorSetting']);//tested



    //Wallet Setting
    Route::get('/wallet', [WalletController::class, 'getWallet']); //tested
    Route::get('/wallet/admin', [WalletController::class, 'getWalletAdmin']); //tested
    Route::post('/wallet/fund', [WalletController::class, 'fundWallet']);
    Route::get('/wallet/paystack-callback', [WalletController::class, 'paystackCallback'])->name('paystack.callback');
    Route::get('/wallet/transactions', [WalletController::class, 'getWalletTransactions']);//tested



    //Farm management System
    Route::get('/farm-types', [FarmTypeController::class, 'index']); // List all farm types //tested
    Route::post('/farm-types', [FarmTypeController::class, 'store']); // Create a new farm type //tested
    Route::get('/farm-types/{id}', [FarmTypeController::class, 'show']); // Show a specific farm type  //tested
    Route::put('/farm-types/{id}', [FarmTypeController::class, 'update']); // Update a specific farm type //tested
    Route::delete('/farm-types/{id}', [FarmTypeController::class, 'destroy']); // Delete a specific farm type

    Route::get('/cost-benefit', [BenefitController::class, 'index']); // List all benefits //tested
    Route::post('/cost-benefit', [BenefitController::class, 'store']); // Create a new benefit //tested
    Route::get('/cost-benefit/{id}', [BenefitController::class, 'show']); // Show a single benefit //tested
    Route::put('/cost-benefit/{id}', [BenefitController::class, 'update']); // Update an existing benefit //tested
    Route::delete('/cost-benefit/{id}', [BenefitController::class, 'destroy']); // Delete a benefit


    // List all farms
    Route::get('/farms', [FarmsController::class, 'index']); //tested
    // Create a new farm
    Route::post('/farms', [FarmsController::class, 'store']); //tested
     // Show a single farm
    Route::get('/farms/{id}', [FarmsController::class, 'show']); //tested
     // Update a specific farm
    Route::put('/farms/{id}', [FarmsController::class, 'update']);//tested
     // Delete a specific farm
    Route::delete('/farms/{id}', [FarmsController::class, 'destroy']);//tested

    Route::get('farms/user-farms/{id}', [FarmsController::class, 'userFarms']); //tested


    // List all farm activities
    Route::get('/farm-activities', [FarmActivityController::class, 'index']); //tested
    // Get farm activities by farm type ID
    Route::get('/farm-activities/farm-type/{id}', [FarmActivityController::class, 'getActivityByFarmType']); //tested
    // Create a new farm activity
    Route::post('/farm-activities', [FarmActivityController::class, 'store']); //tested
    // Show a single farm activity
    Route::get('/farm-activities/{id}', [FarmActivityController::class, 'show']); //tested
    // Update a specific farm activity
    Route::put('/farm-activities/{id}', [FarmActivityController::class, 'update']); //tested
    // Delete a specific farm activity
    Route::delete('/farm-activities/{id}', [FarmActivityController::class, 'destroy']);




    // List all farm tasks
    Route::get('farm-tasks', [FarmTaskController::class, 'index']); //no need
    // Get all tasks for a specific farm
    Route::get('farm-tasks/farm/{id}', [FarmTaskController::class, 'getByFarmTask']); //tested
    // Start a task
    Route::post('farm-tasks/{taskId}/start', [FarmTaskController::class, 'startTask']); //tested
    Route::post('farm-tasks/{taskId}/complete', [FarmTaskController::class, 'CompleteTask']); //tested

    // Complete a task
   // Route::post('farm-tasks/{taskId}/complete', [FarmTaskController::class, 'completeTask']);

    // // Create a new farm task
    // Route::post('farm-tasks', [FarmTaskController::class, 'store']); //no-need
    // // Show a single farm task
    // Route::get('farm-tasks/{id}', [FarmTaskController::class, 'show']); //no-need
    // // Update a specific farm task
    // Route::put('farm-tasks/{id}', [FarmTaskController::class, 'update']); //no-need
    // // Delete a specific farm task
    // Route::delete('farm-tasks/{id}', [FarmTaskController::class, 'destroy']); //no-need


    // Get inventory by farm ID
    Route::get('inventory-farm/{farmId}', [FarmInventoryController::class, 'getInventoryByFarmId']); //tested
    // Add to a specific inventory field
    Route::post('inventory-farm/{farmId}/add', [FarmInventoryController::class, 'addInventory']); //tested
    // Subtract from a specific inventory field
    Route::post('inventory-farm/{farmId}/subtract', [FarmInventoryController::class, 'subtractInventory']); //tested




    //Logistic System Begin
    Route::post('/logistic-bio', [LogisticBioController::class, 'store']); //tested
    Route::get('/logistic-bio', [LogisticBioController::class, 'getAll']); //tested
    Route::get('/logistic-bio/{userId}', [LogisticBioController::class, 'getByUserId']); //tested
    Route::put('/logistic-bio', [LogisticBioController::class, 'update']);
    Route::delete('/logistic-bio', [LogisticBioController::class, 'destroy']);
    Route::get('/logistics/pending', [LogisticBioController::class, 'allPending']); //tetsed
    Route::post('/logistics/accept/{userId}', [LogisticBioController::class, 'verifyVendor']); //tested
    Route::get('/logisics/{userId}/shippings', [ShippingController::class, 'GetAllUserShipping']); //tested - logistic user

    // Shipping CRUD Routes
    Route::get('shippings', [ShippingController::class, 'getAllShippings']); // Get all shipping records //tested
    Route::get('shippings/track-order', [ShippingController::class, 'Trackshipping']); // Get all shipping records //tested
    Route::get('shippings/{id}', [ShippingController::class, 'getSingleShipping']); // Get a single shipping record //tested
    // Route::post('shippings', [ShippingController::class, 'createShipping']); // Create a new shipping record
    // Route::put('shippings/{id}', [ShippingController::class, 'updateShipping']); // Update a shipping record
    // Route::delete('shippings/{id}', [ShippingController::class, 'deleteShipping']); // Delete a shipping record

    // Logistic Management
    Route::post('shippings/{id}/assign-logistic', [ShippingController::class, 'assignLogistic']); //tested // Assign a logistic user to shipping
    Route::post('shippings/{id}/change-status', [ShippingController::class, 'changeStatus']); //tested //  Change the status of shipping
    Route::get('shippings/pending/unassigned', [ShippingController::class, 'getPendingUnassigned']); //tested // Get all pending and unassigned shipping records


    //Drivers Route
    Route::post('/drivers', [DriverController::class, 'store']); //tested
    Route::get('/drivers', [DriverController::class, 'index']);
    Route::get('/drivers/{id}', [DriverController::class, 'show']); //tested
    Route::get('/logistic/{id}/drivers', [DriverController::class, 'logisticDrivers']); //tested

    Route::put('/drivers/{id}', [DriverController::class, 'update']); //tested
    Route::delete('/drivers/{id}', [DriverController::class, 'destroy']);
    Route::post('/drivers/{driverId}/verify', [DriverController::class, 'verifyDriver']); //tested
    Route::post('/drivers/{driverId}/assign-vehicle', [DriverController::class, 'assignVehicle']); //not needed


    Route::post('/vehicles', [VehicleController::class, 'store']); //tested
    Route::get('/vehicles/{id}', [VehicleController::class, 'show']); //tested
    Route::put('/vehicles/{id}', [VehicleController::class, 'update']); //tested
    Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
    Route::get('logistic/{userId}/vehicles', [VehicleController::class, 'index']);//tested


    // Route to add a new admin user
    Route::post('/admin/add', [AdminController::class, 'addAdminUser']); //tested
    // Route to get all admin users
    Route::get('admin/users', [AdminController::class, 'getAdminUsers']); //tested
    // Route to edit admin user permissions
    Route::put('/admin-permissions/{userId}', [AdminController::class, 'editAdminPermission']); //tested
    Route::get('/admin/logs', [LogMessageController::class, 'getLogDetails']); //tested


    //Subscription Route
    Route::get('/subscription/plans', [SubscriptionPlanController::class, 'index']); // List all subscription plans
    Route::put('/subscription/plans/{id}', [SubscriptionPlanController::class, 'update']); // Update a subscription plan

    //checkUserSubscription
    Route::get('/subscription/user/{userid}/check/{planName}', [SubscriptionUserController::class, 'checkUserSubscription']); // Check if user has an active subscription for a plan
    //activateUserSubscription
    Route::post('/subscription/user/{userid}/activate/{id}', [SubscriptionUserController::class, 'activateUserSubscription']); // Activate a user's subscription
    //getUserSubscription
    Route::get('/subscription/user/{id}', [SubscriptionUserController::class, 'getUserSubscriptionDetails']); // Get user subscription details and payments


});


//draft some documentation for the API in the README.md file for subscription
