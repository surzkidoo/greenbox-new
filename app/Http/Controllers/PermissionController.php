<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    // Retrieve all permissions
    public function index(Request $request)
    {


        $permissions = permission::all();
        return response()->json(['status' => 'success', 'data' => $permissions], 200);
    }

   // Get permissions for Admin
    public function getAdminPermissions(): JsonResponse
    {
        $permissions = permission::where('role_for', 'admin')->pluck('name');

        if ($permissions->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No permissions found for admin.'], 404);
        }

        return response()->json(['status' => 'success', 'permissions' => $permissions], 200);
    }

    // Get permissions for User
    public function getUserPermissions(): JsonResponse
    {
        $permissions = permission::where('role_for', 'user')->pluck('name');

        if ($permissions->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No permissions found for user.'], 404);
        }

        return response()->json(['status' => 'success', 'permissions' => $permissions], 200);
    }

    // Create a new permission
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name|max:255',
            'access_type' => 'nullable|string|max:255',
            'role_for' => 'required|string|max:255',

        ]);

        $permission = permission::create($request->only(['name','access_type','role_for']));

        return response()->json(['status' => 'success', 'data' => $permission], 201);
    }

    // Retrieve a specific permission
    public function show($id)
    {
        $permission = permission::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $permission], 200);
    }

    // Update a specific permission
    public function update(Request $request, $id)
    {
        $permission = permission::findOrFail($id);

        $request->validate([
            'name' => 'nullable|string|unique:permissions,name,' . $permission->id . '|max:255',
            'access_type' => 'nullable|string|max:255',
            'role_for' => 'nullable|string|max:255'
        ]);

        $permission->update($request->only(['name','access_type','role_for']));

        return response()->json(['status' => 'success', 'data' => $permission], 200);
    }

    // Delete a specific permission
    public function destroy($id)
    {
        $permission = permission::findOrFail($id);
        $permission->delete();

        return response()->json(['status' => 'success', 'message' => 'Permission deleted successfully.'], 200);
    }


    public function assignPermission(Request $request, $userId)
    {
        $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $user = User::findOrFail($userId);
        $user->permissions()->attach($request->permission_id);

        return response()->json(['status' => 'success', 'message' => 'Permission assigned successfully.'], 200);
    }

    // Revoke permission from a user
    public function revokePermission(Request $request, $userId)
    {
        $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $user = User::findOrFail($userId);
        $user->permissions()->detach($request->permission_id);

        return response()->json(['status' => 'success', 'message' => 'Permission revoked successfully.'], 200);
    }
}
