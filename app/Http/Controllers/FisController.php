<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\fis_bio;
use App\Models\fis_bank;
use App\Models\fis_farm;
use App\Models\permission;
use App\Models\fis_nextkind;
use App\Models\notification;
use Illuminate\Http\Request;
use App\Models\fis_guarantor;
use App\Services\TwilioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FisController extends Controller
{
    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }


    public function getAllFisRecords(Request $request)
    {
        // Retrieve status from query parameters, defaulting to 'all'
        $status = $request->query('status', 'all');

        // Build the query for users with fisBio relationship
        $query = User::whereHas('fisBio', function ($query) use ($status) {
            if ($status === 'verified') {
                $query->where('status', 'activated');
            } elseif ($status === 'pending') {
                $query->where('status', 'pending');
            } elseif ($status === 'rejected') {
                $query->where('status', 'rejected');
            } elseif ($status === 'deactivated') {
                $query->where('status', 'deactivated');
            }
        });

        // Fetch users with related records and paginate results
        $users = $query->with([
            'fisBio',
            'fisFarm',
            'fisBank',
            'fisGuarantor',
            'fisNextKind',
        ])->paginate(10);

        // Check if any records were found
        if ($users->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No records found'], 404);
        }

        $fisUser = User::whereHas('fisBio')->count();

        $fisUserpending = User::whereHas('fisBio', function ($query) {
            $query->where('status', 'pending');
        })->count();


        $fisUserdeactivated = User::whereHas('fisBio', function ($query) {
            $query->where('status', 'deactivated');
        })->count();




        return response()->json([
            'status' => 'success',
            'message' => 'Records found',
            'data' => ['users' => $users, 'all_farmer' => $fisUser, 'all_pending' => $fisUserpending, 'all_deactivated' => $fisUserdeactivated],
        ], 200);
    }

    // Create new records for bio, farm, bank, guarantor, and next of kin
    public function createFisRecord(Request $request)
    {
        $validationRules = [
            // Bio fields
            'legalname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'id' => 'required|string|max:100',
            'id_type' => 'required|string|max:100',
            'gender' => 'required|string|max:10',
            'dob' => 'required|date',
            'nationality' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'lga' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'ward' => 'required|string|max:100',
            'address' => 'required|string|max:255',

            // Farm fields
            'farm_name' => 'required|string|max:255',
            'prod_type' => 'required|string|max:255',
            'ownership' => 'required|string|max:255',
            'farm_geo' => 'required|string|max:255',
            'soil_type' => 'required|string|max:255',
            'soil_test' => 'required|string|max:255',
            'farm_size' => 'required|string|max:100',

            // Bank fields
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'bvn' => 'required|string|max:100',

            // Guarantor fields
            'guarantor_fullname' => 'required|string|max:255',
            'guarantor_email' => 'required|email|max:255',
            'guarantor_phone' => 'required|string|max:100',
            'guarantor_signature' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'guarantor_res_address' => 'required|string|max:255',

            // Next of Kin fields
            'next_of_kin_fullname' => 'required|string|max:255',
            'next_of_kin_email' => 'required|email|max:255',
            'next_of_kin_phone' => 'required|string|max:100',
            'next_of_kin_res_address' => 'required|string|max:255',
            'next_of_kin_signature' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ];

        // Create the validator instance
        $validator = Validator::make($request->all(), $validationRules);

        $user = User::find(Auth::id());
        if ($user->fisBio()->exists()) {
            return response()->json(['status' => 'error', 'message' => 'You already have a Fis record.'], 400);
        }


        if ($validator->fails()) {
            // Customize the error response for API requests
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if ($request->hasFile('guarantor_signature')) {
            $image = $request->file('guarantor_signature');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/fis/gr'), $imageName);
        }

        if ($request->hasFile('next_of_kin_signature')) {
            $image2 = $request->file('next_of_kin_signature');
            $imageName2 = time() . '.' . $image2->getClientOriginalExtension();
            $image2->move(public_path('images/fis/next'), $imageName2);
        }

        DB::beginTransaction();

        try {
            // Create each record separately
            $bio = fis_bio::create([
                'legalname' => $validated['legalname'],
                'email' => $validated['email'],
                'id_number' => $validated['id'],
                'id_type' => $validated['id_type'],
                'gender' => $validated['gender'],
                'dob' => $validated['dob'],
                'nationality' => $validated['nationality'],
                'state' => $validated['state'],
                'lga' => $validated['lga'],
                'city' => $validated['city'],
                'ward' => $validated['ward'],
                'address' => $validated['address'],
                'user_id' => Auth::id(),
            ]);

            $farm = fis_farm::create([
                'farm_name' => $validated['farm_name'],
                'prod_type' => $validated['prod_type'],
                'ownership' => $validated['ownership'],
                'farm_geo' => $validated['farm_geo'],
                'soil_type' => $validated['soil_type'],
                'soil_test' => $validated['soil_test'],
                'farm_size' => $validated['farm_size'],
                'user_id' => Auth::id(),
            ]);

            $bank = fis_bank::create([
                'account_name' => $validated['account_name'],
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['account_number'],
                'bvn' => $validated['bvn'],
                'user_id' => Auth::id(),
            ]);

            $guarantor = fis_guarantor::create([
                'fullname' => $validated['guarantor_fullname'],
                'email' => $validated['guarantor_email'],
                'phone' => $validated['guarantor_phone'],
                'signature_url' => $imageName,
                'res_address' => $validated['guarantor_res_address'],
                'user_id' => Auth::id(),
            ]);

            $nextOfKin = fis_nextkind::create([
                'fullname' => $validated['next_of_kin_fullname'],
                'email' => $validated['next_of_kin_email'],
                'phone' => $validated['next_of_kin_phone'],
                'signature_url' => $imageName2 ?? null,
                'res_address' => $validated['next_of_kin_res_address'],
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'All records created successfully.',
                'data' => [
                    'bio' => $bio,
                    'farm' => $farm,
                    'bank' => $bank,
                    'guarantor' => $guarantor,
                    'next_of_kin' => $nextOfKin,
                ]

            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' =>  'Failed to create records. Please try again.' . $e], 500);
        }
    }


    // Get a specific record by user ID
    public function getFisByUserId($id)
    {
        $bio = fis_bio::where('user_id', $id)->first();
        if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
        }

        $farm = fis_farm::where('user_id', $id)->first();
        $bank = fis_bank::where('user_id', $id)->first();
        $guarantor = fis_guarantor::where('user_id', $id)->first();
        $nextOfKin = fis_nextkind::where('user_id', $id)->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'bio' => $bio,
                'farm' => $farm,
                'bank' => $bank,
                'guarantor' => $guarantor,
                'next_of_kin' => $nextOfKin,
            ]
        ], 200);
    }

    // Get a specific record by user ID
    public function activateFarmer($id)
    {
        $user = User::where('id', $id)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
        }



        $user->fis_verified = true;

        $bio = fis_bio::where('user_id', $id)->first();

         if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'Not A Farmer yet'], 404);
        }
        $bio->status = "activated";
        $bio->save();




        //add permissions
        $permissions = permission::where('role_for', 'user')->pluck('id');
        $user->permissions()->sync($permissions);
        $user->save();


        notification::create([
            'user_id' => $user->id,
            'data' => "Your Account is Verified as a Farmer",
        ]);

        //$this->twilio->sendSms($user->phone, "Your Account is Verified as a Farmer"');


        return response()->json([
            'status' => 'success',
            'message' => 'farmer is now verified',
        ], 200);
    }

    public function deactivateFarmer($userID)
    {
        $user = User::where('id', $userID)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
        }

        $user->fis_verified = false;

        $permissions = permission::where('role_for', 'user')->pluck('id');
        $user->permissions()->detach($permissions);
        $user->save();

        $bio = fis_bio::where('user_id', $userID)->first();

        if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'Not A Farmer yet'], 404);
        }

        $bio->status = "deactivated";

        notification::create([
            'user_id' => $user->id,
            'data' => "Your Fis Account is Deactivated",
        ]);

        // $this->twilio->sendSms($user->phone, 'Your Fis Account is Deactivated');


        return response()->json([
            'status' => 'success',
            'message' => 'farmer is now deactivated',
        ], 200);
    }


    public function rejectPending($userID)
    {
        $user = User::where('id', $userID)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
        }


        $bio = fis_bio::where('user_id', $userID)->first();
        $bio->status = "rejected";

        notification::create([
            'user_id' => $user->id,
            'data' => "Your Fis Account Activation Failed/rejected",
        ]);


        // $this->twilio->sendSms($user->phone, 'Your Fis Account Activation Failed/rejected');

        return response()->json([
            'status' => 'success',
            'message' => 'farmer is now deactivated',
        ], 200);
    }

    public function updateFisRecord(Request $request, $id)
    {
        // Find the Bio record by user ID
        $bio = fis_bio::where('user_id', $id)->first();
        if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
        }

        // Validation rules
        $validationRules = [
            // Bio fields
            'legalname' => 'string|max:255',
            'email' => 'email|max:255',
            'Id' => 'string|max:100',
            'Id_type' => 'string|max:100',
            'gender' => 'string|max:10',
            'dob' => 'date',
            'nationality' => 'string|max:100',
            'state' => 'string|max:100',
            'lga' => 'string|max:100',
            'city' => 'string|max:100',
            'ward' => 'string|max:100',
            'address' => 'string|max:255',

            // Farm fields
            'farm_name' => 'string|max:255',
            'prod_type' => 'string|max:255',
            'ownership' => 'string|max:255',
            'farm_geo' => 'string|max:255',
            'soil_type' => 'string|max:255',
            'soil_test' => 'string|max:255',
            'farm_size' => 'string|max:100',

            // Bank fields
            'account_name' => 'string|max:255',
            'bank_name' => 'string|max:255',
            'account_number' => 'string|max:100',
            'bvn' => 'string|max:100',

            // Guarantor fields
            'guarantor_fullname' => 'string|max:255',
            'guarantor_email' => 'email|max:255',
            'guarantor_phone' => 'string|max:100',
            'guarantor_signature' => 'image|mimes:jpeg,png,jpg|max:2048',
            'guarantor_res_address' => 'string|max:255',

            // Next of Kin fields
            'next_of_kin_fullname' => 'string|max:255',
            'next_of_kin_email' => 'email|max:255',
            'next_of_kin_phone' => 'string|max:100',
            'next_of_kin_signature' => 'image|mimes:jpeg,png,jpg|max:2048',
            'next_of_kin_res_address' => 'string|max:255',
        ];

        // Validate the request
        $validated = $request->validate($validationRules);

        DB::beginTransaction();

        try {
            // Update the Bio record
            $bio->update([
                'legalname' => $validated['legalname'] ?? $bio->legalname,
                'email' => $validated['email'] ?? $bio->email,
                'Id_number' => $validated['Id'] ?? $bio->Id_number,
                'Id_type' => $validated['Id_type'] ?? $bio->Id_type,
                'gender' => $validated['gender'] ?? $bio->gender,
                'dob' => $validated['dob'] ?? $bio->dob,
                'nationality' => $validated['nationality'] ?? $bio->nationality,
                'state' => $validated['state'] ?? $bio->state,
                'lga' => $validated['lga'] ?? $bio->lga,
                'city' => $validated['city'] ?? $bio->city,
                'ward' => $validated['ward'] ?? $bio->ward,
                'address' => $validated['address'] ?? $bio->address,
                'status' => 'pending'
            ]);

            // Update the Farm record
            $farm = fis_farm::where('user_id', $id)->first();
            if ($farm) {
                $farm->update([
                    'farm_name' => $validated['farm_name'] ?? $farm->farm_name,
                    'prod_type' => $validated['prod_type'] ?? $farm->prod_type,
                    'ownership' => $validated['ownership'] ?? $farm->ownership,
                    'farm_geo' => $validated['farm_geo'] ?? $farm->farm_geo,
                    'soil_type' => $validated['soil_type'] ?? $farm->soil_type,
                    'soil_test' => $validated['soil_test'] ?? $farm->soil_test,
                    'farm_size' => $validated['farm_size'] ?? $farm->farm_size,
                ]);
            }

            // Update the Bank record
            $bank = fis_bank::where('user_id', $id)->first();
            if ($bank) {
                $bank->update([
                    'account_name' => $validated['account_name'] ?? $bank->account_name,
                    'bank_name' => $validated['bank_name'] ?? $bank->bank_name,
                    'account_number' => $validated['account_number'] ?? $bank->account_number,
                    'bvn' => $validated['bvn'] ?? $bank->bvn,
                ]);
            }

            // Update the Guarantor record
            $guarantor = fis_guarantor::where('user_id', $id)->first();
            if ($guarantor) {
                if ($request->hasFile('guarantor_signature')) {
                    $guarantorImage = $request->file('guarantor_signature');
                    $guarantorImageName = time() . '.' . $guarantorImage->getClientOriginalExtension();
                    $guarantorImage->move(public_path('images/fis/gr'), $guarantorImageName);
                    $guarantor->update(['signature_url' => $guarantorImageName]);
                }

                $guarantor->update([
                    'fullname' => $validated['guarantor_fullname'] ?? $guarantor->fullname,
                    'email' => $validated['guarantor_email'] ?? $guarantor->email,
                    'phone' => $validated['guarantor_phone'] ?? $guarantor->phone,
                    'res_address' => $validated['guarantor_res_address'] ?? $guarantor->res_address,
                ]);
            }

            // Update the Next of Kin record
            $nextOfKin = fis_nextkind::where('user_id', $id)->first();
            if ($nextOfKin) {
                if ($request->hasFile('next_of_kin_signature')) {
                    $nextOfKinImage = $request->file('next_of_kin_signature');
                    $nextOfKinImageName = time() . '.' . $nextOfKinImage->getClientOriginalExtension();
                    $nextOfKinImage->move(public_path('images/fis/next'), $nextOfKinImageName);
                    $nextOfKin->update(['signature_url' => $nextOfKinImageName]);
                }

                $nextOfKin->update([
                    'fullname' => $validated['next_of_kin_fullname'] ?? $nextOfKin->fullname,
                    'email' => $validated['next_of_kin_email'] ?? $nextOfKin->email,
                    'phone' => $validated['next_of_kin_phone'] ?? $nextOfKin->phone,
                    'res_address' => $validated['next_of_kin_res_address'] ?? $nextOfKin->res_address,
                ]);
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Record updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update record.' . $e], 500);
        }
    }


    // Delete the records by user ID
    public function deleteFisRecord($id)
    {
        DB::beginTransaction();
        try {
            fis_bio::where('user_id', $id)->delete();
            fis_farm::where('user_id', $id)->delete();
            fis_bank::where('user_id', $id)->delete();
            fis_guarantor::where('user_id', $id)->delete();
            fis_nextkind::where('user_id', $id)->delete();

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Record deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to delete record.' . $e], 500);
        }
    }
}
