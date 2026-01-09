<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Admin login
     * POST /api/admin/auth/login
     */
    public function login(Request $request)
    {
        // Rate limiting
        $key = 'admin-login:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find admin user
        $admin = User::where('email', $request->email)
                    ->where('is_admin', true)
                    ->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            RateLimiter::hit($key, 300); // 5 minutes decay
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if admin is suspended
        if ($admin->is_suspended) {
            return response()->json([
                'success' => false,
                'message' => 'Your admin account has been suspended',
            ], 403);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($key);

        // Update last login
    $admin->updateLastLogin();

    // Create token with admin abilities
    $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

    // Log admin login
    \Log::info('Admin login', [
        'admin_id' => $admin->id,
        'email' => $admin->email,
        'role' => $admin->admin_role?->value,
        'ip' => $request->ip(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'admin' => [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'phone_number' => $admin->phone_number,
            'role' => $admin->admin_role?->value,
            'role_label' => $admin->getRoleLabel(),
            'permissions' => $admin->permissions,
            'is_super_admin' => $admin->isSuperAdmin(),
        ],
    ], 200);
    }

    /**
     * Get current admin user
     * GET /api/admin/auth/me
     */
    public function me(Request $request)
    {
        $admin = $request->user();

        return response()->json([
            'success' => true,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'phone_number' => $admin->phone_number,
                'created_at' => $admin->created_at,
            ],
        ]);
    }

    /**
     * Admin logout
     * POST /api/admin/auth/logout
     */
    public function logout(Request $request)
    {
        $admin = $request->user();
        
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        \Log::info('Admin logout', [
            'admin_id' => $admin->id,
            'email' => $admin->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Revoke all admin tokens (logout from all devices)
     * POST /api/admin/auth/logout-all
     */
    public function logoutAll(Request $request)
    {
        $admin = $request->user();
        
        // Revoke all tokens
        $admin->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }

    /**
     * Change admin password
     * POST /api/admin/auth/change-password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $admin = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 401);
        }

        // Update password
        $admin->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Revoke all other tokens
        $admin->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
}