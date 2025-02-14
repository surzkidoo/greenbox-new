<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\setting;
use App\Models\LogMessage;
use App\Models\permission;
use Illuminate\Support\Str;
use App\Models\notification;
use Illuminate\Http\Request;
use App\Mail\PasswordResetMail;
use App\Services\TwilioService;
use PhpParser\Node\Stmt\Return_;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Mail\EmailVerificationMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class AuthController extends Controller
{

    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }

    // User registration
    public function register(RegisterRequest $request):JsonResponse
    {

        // $this->twilio->sendSms('+18777804236', 'Your Token is !2327352');

        DB::beginTransaction();

       $checkmail = User::where('email',$request->email)->first();

       if($checkmail){
       return response()->json([
            'status' => 'error',
            'message' => 'User Exist with that email',
        ], 422);
       }

        // Create the new user with validated data
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'occupation' => $request->occupation,
            'state' => $request->state,
            'lga' => $request->lga,
            'gender' => $request->gender,
            'refer_by' => $request->refer_by,
            'password' => Hash::make($request->password),
            'account_status' => 'pending',
            'email_verified' => false,
        ]);

                // Generate a unique referral code using user ID and random string
            $referralCode = strtoupper(Str::random(5)) . $user->id;

            // Save the referral code in the database, for example, in the `users` table
            $user->referral_code = $referralCode;
            $user->save();

           // Generate a unique email verification token
           $verificationToken = Str::random(60);

           // Save the verification token (in the `email_verifications` table)
           $user->emailVerification()->create([
               'token' => $verificationToken,
               'created_at' => now(),
           ]);

           $user->wallet()->create([
            'balance' => 0,
           ]);


           $verificationUrl = 'https://app.hibgreenbox.com/email/verify/' . $verificationToken . '?email=' . urlencode($user->email);

           Mail::to($user->email)->send(new EmailVerificationMail($verificationUrl));

        DB::commit();
        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful, please verify your email.',
            'user'=>$user
        ], 201);
    }

        public function verifyEmail($token)
    {

        DB::beginTransaction();

        // Check if the token exists in the email_verifications table
        $verification = DB::table('email_verifications')->where('token', $token)->first();

        if (!$verification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid verification token.'
            ], 400);
        }

        // Find the user associated with the token
        $user = User::where('email', $verification->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        // Mark the user's email as verified
        $user->email_verified = true;
        $user->account_status = 'active';  // Optionally change account status to active
        $user->save();

        // Delete the verification token from the database
        DB::table('email_verifications')->where('token', $token)->delete();

        $defaultSetting = [
            'user_id' => $user->id,
            '2fa' => false,
            'live_location' => false,
            'team_link' => true,
            'weather' => true,
            'humidity' => false,
            'default_shipping' => null,
        ];
        setting::create($defaultSetting);

        notification::create([
            'user_id' =>$user->id,
            'data' => "Welcome to HiB GreenBox",
        ]);


        DB::commit();
        return response()->json([
            'status' => 'success',
            'message' => 'Email successfully verified.',
            'user' => $user,
        ], 200);
    }


    public function login(Request $request): JsonResponse
    {
        // Validate incoming request data
        $rules = [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'type' => 'required|string',
        ];

        // Define custom error messages (optional)
        $messages = [
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'password.required' => 'The password field is mandatory.',
            'type.required' => 'The user type is required.',
        ];

        // Create the validator instance
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            // Customize the error response for API requests
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->input('email');
        $throttleKey = 'login_attempts_' . Str::lower($email);

        // Check if user exists
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->logMessage($request, 'security', 'Failed login attempt: User does not exist');
            return response()->json([
                'status' => 'error',
                'message' => 'User does not exist'
            ], 401);
        }

        // Check if the user role matches the requested type
        if ($request->type !== $user->role) {
            $this->logMessage($request, 'security', 'Failed login attempt: Invalid account access');
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Account, you do not have access to this Dashboard.'
            ], 401);
        }

        // Apply throttling to prevent brute force attacks
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->logMessage($request, 'security', 'Too many login attempts');
            return response()->json([
                'status' => 'error',
                'message' => 'Too many login attempts. Please try again later (5 minutes later).'
            ], 429);
        }

        // Attempt login
        if (!Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($throttleKey, 300);
            $this->logMessage($request, 'security', 'Failed login attempt: Incorrect credentials');
            return response()->json([
                'status' => 'error',
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        // Reset throttle count on successful login
        RateLimiter::clear($throttleKey);

        // Generate a token for the authenticated user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log the successful login
        $this->logMessage($request, 'login', 'Successful login');

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'access_token' => $token,
            'user' => $user
        ], 200);
    }

    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        // Validate the email input
        $rules = [
            'email' => 'required|string|email|exists:users,email',
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

        $user = User::where('email', $request->email)->first();

        // Generate a unique token for password reset
        $token = Str::random(60);

        // Save the token in your database or in a password resets table
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        // Send password reset link
        $resetUrl = 'https://app.hibgreenbox.com/password/reset/' . $token . '?email=' . urlencode($user->email);
        Mail::to($user->email)->send(new PasswordResetMail($resetUrl));

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset link sent to your email.'
        ], 200);
    }

    // Reset password
    public function resetPassword(Request $request): JsonResponse
    {
        // Validate the request data
        $rules = [
            'email' => 'required|string|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
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

        // Check if the token exists in the password resets table
        $resetRecord = DB::table('password_reset_tokens')
                        ->where('email', $request->email)
                        ->where('token', $request->token)
                        ->first();

        if (!$resetRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired password reset token.'
            ], 400);
        }

        // Find the user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        // Update the user's password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the reset token after password is updated
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successfully.'
        ], 200);
    }



    protected function logMessage(Request $request, string $type, string $message): void
{
    LogMessage::create([
        'ip_address' => $request->ip(), // Capture the client's IP address
        'device_info' => $request->header('User-Agent'), // Capture user agent/device details
        'message' => $message, // Log the action or reason
        'type' => $type, // Type of log (e.g., login, security)
        'role' => $request->input('type')=='vendor' ? 'user': $request->input('type', null), // Role of the user if available
        'email' => $request->input('email', null), // Email of the user if available
        'user_id' => optional(Auth::user())->id, // Authenticated user ID or null
    ]);
}

}
