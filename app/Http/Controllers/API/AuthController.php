<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request)
    {
        // Format phone number
        $phone = $this->formatPhone($request->phone);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'phone' => $phone,
            'county_id' => $request->county_id,
            'constituency_id' => $request->constituency_id,
            'ward_id' => $request->ward_id,
        ]);

        // Generate and send OTP
        $otpResult = $this->otpService->generateAndSendOtp($user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your phone number with the OTP sent.',
            'data' => [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'otp_sent' => $otpResult['sms_sent'],
            ],
        ], 201);
    }

    /**
     * Login user (request OTP).
     */
    public function login(LoginRequest $request)
    {
        $phone = $this->formatPhone($request->phone);

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found. Please register first.',
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        // Generate and send OTP
        $otpResult = $this->otpService->generateAndSendOtp($user);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone number.',
            'data' => [
                'user_id' => $user->id,
                'otp_sent' => $otpResult['sms_sent'],
            ],
        ]);
    }

    /**
     * Verify OTP and return token.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = User::findOrFail($request->user_id);

        $result = $this->otpService->verifyOtp($user, $request->otp);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'data' => [
                'user' => $user->load(['county', 'constituency', 'ward']),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($request->user_id);

        $result = $this->otpService->resendOtp($user);

        return response()->json([
            'success' => true,
            'message' => 'OTP resent successfully.',
            'data' => [
                'otp_sent' => $result['sms_sent'],
            ],
        ]);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Format phone number to standard format.
     */
    protected function formatPhone(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If it doesn't start with country code, add 254 for Kenya
        if (!str_starts_with($phone, '254')) {
            // Remove leading 0 if present
            if (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }
            $phone = '254' . $phone;
        }

        return $phone;
    }
}

