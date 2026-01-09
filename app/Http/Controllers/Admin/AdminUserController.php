<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\{AdminRole, Permission};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    /**
     * Get all admin users
     * GET /api/admin/admins
     */
    public function index(Request $request)
    {
        $admins = User::admins()
                    ->with('ward.constituency.county')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    /**
     * Create new admin user
     * POST /api/admin/admins
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:8',
            'admin_role' => 'required|in:' . implode(',', array_map(fn($r) => $r->value, AdminRole::all())),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'is_admin' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Assign role and default permissions
        $role = AdminRole::from($request->admin_role);
        $admin->assignRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Admin user created successfully',
            'admin' => $admin,
        ], 201);
    }

    /**
     * Update admin user
     * PUT /api/admin/admins/{user}
     */
    public function update(Request $request, User $user)
    {
        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'User is not an admin',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'admin_role' => 'sometimes|in:' . implode(',', array_map(fn($r) => $r->value, AdminRole::all())),
            'permissions' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update basic info
        $user->update($request->only(['name', 'email']));

        // Update role if provided
        if ($request->has('admin_role')) {
            $role = AdminRole::from($request->admin_role);
            $user->assignRole($role);
        }

        // Update permissions if provided (override default)
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin user updated successfully',
            'admin' => $user->fresh(),
        ]);
    }

    /**
     * Delete admin user
     * DELETE /api/admin/admins/{user}
     */
    public function destroy(User $user)
    {
        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'User is not an admin',
            ], 400);
        }

        // Prevent deleting super admin (or last super admin)
        if ($user->isSuperAdmin()) {
            $superAdminCount = User::superAdmins()->count();
            if ($superAdminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last super admin',
                ], 400);
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin user deleted successfully',
        ]);
    }

    /**
     * Get available roles and permissions
     * GET /api/admin/admins/roles-permissions
     */
    public function getRolesAndPermissions()
    {
        $roles = array_map(function($role) {
            return [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
                'permissions' => $role->defaultPermissions(),
            ];
        }, AdminRole::all());

        $permissions = Permission::grouped();
        $permissionsFormatted = [];
        foreach ($permissions as $category => $perms) {
            $permissionsFormatted[$category] = array_map(fn($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ], $perms);
        }

        return response()->json([
            'success' => true,
            'roles' => $roles,
            'permissions' => $permissionsFormatted,
        ]);
    }
}