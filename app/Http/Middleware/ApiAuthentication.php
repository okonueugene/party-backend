<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token required.',
            ], 401);
        }

        // Check if Sanctum is installed
        if (class_exists(\Laravel\Sanctum\PersonalAccessToken::class)) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid authentication token.',
                ], 401);
            }

            // Attach the user to the request
            $request->setUserResolver(function () use ($accessToken) {
                return $accessToken->tokenable;
            });
        } else {
            // Fallback: Simple token validation (for development)
            // In production, you should install Laravel Sanctum: composer require laravel/sanctum
            return response()->json([
                'success' => false,
                'message' => 'Laravel Sanctum is required for API authentication. Please install it: composer require laravel/sanctum',
            ], 500);
        }

        return $next($request);
    }
}

