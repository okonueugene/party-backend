<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        if ($user->is_suspended) {
            return response()->json([
                'success' => false,
                'message' => 'Your admin account has been suspended.',
            ], 403);
        }

        // Check token abilities
        if (!$user->tokenCan('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token abilities.',
            ], 403);
        }

        return $next($request);
    }
}