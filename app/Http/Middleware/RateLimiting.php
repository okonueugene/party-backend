<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;

class RateLimiting
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $action  The action being rate limited (e.g., 'register', 'post')
     */
    public function handle(Request $request, Closure $next, string $action = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $action);

        $maxAttempts = $this->getMaxAttempts($action);
        $decayMinutes = $this->getDecayMinutes($action);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Clear rate limit on success for certain actions
        if ($response->isSuccessful() && in_array($action, ['register', 'login'])) {
            RateLimiter::clear($key);
        }

        return $response;
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request, string $action): string
    {
        $identifier = $request->ip();

        if ($action === 'register' || $action === 'login') {
            $identifier = $request->input('phone', $request->ip());
        } elseif ($request->user()) {
            $identifier = $request->user()->id;
        }

        return "rate_limit:{$action}:{$identifier}";
    }

    /**
     * Get max attempts for the action.
     */
    protected function getMaxAttempts(string $action): int
    {
        return match ($action) {
            'register' => 3, // 3 registrations per hour
            'login' => 5, // 5 login attempts per 15 minutes
            'post' => 10, // 10 posts per hour
            default => 60, // 60 requests per minute
        };
    }

    /**
     * Get decay minutes for the action.
     */
    protected function getDecayMinutes(string $action): int
    {
        return match ($action) {
            'register' => 60, // 1 hour
            'login' => 15, // 15 minutes
            'post' => 60, // 1 hour
            default => 1, // 1 minute
        };
    }
}

