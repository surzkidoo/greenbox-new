<?php
namespace App\Http\Controllers;

use App\Models\bio;
use App\Models\User;
use App\Models\logisticBio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LogisticBioController extends Controller
{
    // Create a new bio
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'reg_no' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:logistic_bios|max:255',
            'phone' => 'required|string|max:20',
            'office_address' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'social' => 'nullable|string|max:255',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'coverage_area' => 'required|string|max:255',
            'service_type' => 'required|string',
            'fleet_info' => 'required|string|max:255',
            'max_weight' => 'required|string|max:255',
            'special_handle' => 'required|string|max:255',
            'insurance_coverage' => 'required|string|max:255',
            'tracking_capability' => 'required|string|max:255',
            'licenses_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'insurance_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'terms_conditions_pdf' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'tax_tin' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'pricing_structure' => 'required|string|max:255',
            'payment_method' => 'required|string|max:255',
            'service_level_agreement' => 'required|string|max:255',
        ]);

        $bioData = $request->only([
            'business_name', 'reg_no', 'contact_name', 'email', 'phone',
            'office_address', 'website', 'social', 'coverage_area',
            'service_type', 'fleet_info', 'max_weight', 'special_handle',
            'insurance_coverage', 'tracking_capability', 'tax_tin','pricing_structure',
             'payment_method', 'service_level_agreement'
        ]);

         $bioData['user_id'] = Auth::id();

        // $bioData['service_type'] = expl(',', $bioData['service_type']);

        // Handle file uploads
        if ($request->hasFile('logo_image')) {
            $bioData['logo_image'] = $request->file('logo_image')->store('bios/logos', 'public');
        }
        if ($request->hasFile('licenses_image')) {
            $bioData['licenses_image'] = $request->file('licenses_image')->store('bios/licenses', 'public');
        }
        if ($request->hasFile('insurance_image')) {
            $bioData['insurance_image'] = $request->file('insurance_image')->store('bios/insurance', 'public');
        }
        if ($request->hasFile('terms_conditions_pdf')) {
            $bioData['terms_conditions_pdf'] = $request->file('terms_conditions_pdf')->store('bios/tax', 'public');
        }

        if ($request->hasFile('tax_tin')) {
            $bioData['tax_tin'] = $request->file('tax_tin')->store('bios/terms', 'public');
        }

        $bio = logisticBio::create($bioData);

        return response()->json(['status' => 'success', 'bio' => $bio], 201);
    }

    // Read a user's bio
    public function show(): JsonResponse
    {
        $bio = logisticBio::where('user_id', Auth::id())->first();


        if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'Bio not found.'], 404);
        }

        $bio->service_type = explode(',', $bio->service_type);


        return response()->json(['status' => 'success', 'bio' => $bio], 200);
    }

    // Update a user's bio
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'reg_no' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:20',
            'office_address' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'social' => 'nullable|string|max:255',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'coverage_area' => 'required|string|max:255',
            'service_type' => 'required|string|max:255',
            'fleet_info' => 'required|string|max:255',
            'max_weight' => 'required|string|max:255',
            'special_handle' => 'required|string|max:255',
            'insurance_coverage' => 'required|string|max:255',
            'tracking_capability' => 'required|string|max:255',
            'licenses_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'insurance_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'terms_conditions_pdf' => 'nullable|file|mimes:pdf|max:2048',
            'tax_tin' => 'nullable|file|mimes:pdf|max:2048',
            'pricing structure' => 'required|string|max:255',
            'payment_method' => 'required|string|max:255',
            'service_level_agreement' => 'required|string|max:255',
        ]);

        $bio = User::has('logisticBio')->with('logisticBio')->where('user_id', Auth::id())->first();

        if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        $bioData = $request->only([
            'business_name', 'reg_no', 'contact_name', 'email', 'phone',
            'office_address', 'website', 'social', 'coverage_area',
            'service_type', 'fleet_info', 'max_weight', 'special_handle',
            'insurance_coverage', 'tracking_capability', 'tax_tin',
            'pricing structure', 'payment_method', 'service_level_agreement'
        ]);

        if (isset($bioData['service_type'])) {
            $bioData['service_type'] = implode(',', $bioData['service_type']);
        }

        // Handle file uploads and deletion of old files
        if ($request->hasFile('logo_image')) {
            if ($bio->logo_image) {
                Storage::disk('public')->delete($bio->logo_image);
            }
            $bioData['logo_image'] = $request->file('logo_image')->store('bios/logos', 'public');
        }
        if ($request->hasFile('licenses_image')) {
            if ($bio->licenses_image) {
                Storage::disk('public')->delete($bio->licenses_image);
            }
            $bioData['licenses_image'] = $request->file('licenses_image')->store('bios/licenses', 'public');
        }
        if ($request->hasFile('insurance_image')) {
            if ($bio->insurance_image) {
                Storage::disk('public')->delete($bio->insurance_image);
            }
            $bioData['insurance_image'] = $request->file('insurance_image')->store('bios/insurance', 'public');
        }
        if ($request->hasFile('terms_conditions_pdf')) {
            if ($bio->terms_conditions_pdf) {
                Storage::disk('public')->delete($bio->terms_conditions_pdf);
            }
            $bioData['terms_conditions_pdf'] = $request->file('terms_conditions_pdf')->store('bios/terms', 'public');
        }


        if ($request->hasFile('tax_tin')) {
            if ($bio->tax_tin) {
                Storage::disk('public')->delete($bio->tax_tin);
            }
            $bioData['tax_tin'] = $request->file('tax_tin')->store('bios/tax', 'public');
        }

        $bio->update($bioData);

        return response()->json(['status' => 'success', 'bio' => $bio], 200);
    }

    // Delete a user's bio
    public function destroy(): JsonResponse
    {
        $bio = logisticBio::where('user_id', Auth::id())->first();

        if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'Bio not found.'], 404);
        }

        // Delete the files
        if ($bio->logo_image) {
            Storage::disk('public')->delete($bio->logo_image);
        }
        if ($bio->licenses_image) {
            Storage::disk('public')->delete($bio->licenses_image);
        }
        if ($bio->insurance_image) {
            Storage::disk('public')->delete($bio->insurance_image);
        }

        if ($bio->terms_conditions_pdf) {
            Storage::disk('public')->delete($bio->terms_conditions_pdf);
        }

        if ($bio->tax_tin) {
            Storage::disk('public')->delete($bio->tax_tin);
        }

        $bio->delete();

        return response()->json(['status' => 'success', 'message' => 'Bio deleted successfully.'], 200);
    }

    public function getByUserId($userId): JsonResponse
    {
        $bio = User::has('logisticBio')->with('logisticBio')->where('id', $userId)->first();

        if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'Bio not found.'], 404);
        }

        return response()->json(['status' => 'success', 'bio' => $bio], 200);
    }

    public function getAll()
    {
        $bio = User::has('logisticBio')->with(['logisticBio','driver','vehicle'])->get();

        if (!$bio) {
            return response()->json(['status' => 'error', 'message' => 'Bio not found.'], 404);
        }

        return response()->json(['status' => 'success', 'bio' => $bio], 200);
    }


    public function allPending(): JsonResponse
    {
        $users = User::has('logisticBio')
            ->with('logisticBio')
            ->where('vendor_verified', false)
            ->get();

        if ($users->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No pending logistic registrations found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Get All Pending Logistic successfully.',
            'data' => $users
        ], 200);
    }


    public function verifyVendor($userId): JsonResponse
    {
        $user = User::has('logisticBio')
            ->with('logisticBio')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        if ($user->vendor_verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is already verified.'
            ], 400);
        }

        $user->vendor_verified = true;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User activated successfully.'
        ], 200);
    }


}
