<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HostPinnacleSmsService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected HostPinnacleSmsService $HostPinnacleSmsService;
    protected OtpService $otpService;

    public function __construct(HostPinnacleSmsService $HostPinnacleSmsService, OtpService $otpService)
    {
        $this->HostPinnacleSmsService = $HostPinnacleSmsService;
        $this->otpService             = $otpService;
    }

    /**
     * Request OTP for login/registration
     * POST /api/auth/request-otp
     */
    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => [
                'required',
                'string',
                'regex:/^(\+?254|0)?[17]\d{8}$/',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone_number number format',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $phoneNumber = $request->phone_number;

        // Format phone_number number
        $formattedPhone = $this->formatPhoneNumber($phoneNumber);

        // Validate phone_number number
        if (! $this->HostPinnacleSmsService->validatePhoneNumber($formattedPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Kenyan phone_number number',
            ], 422);
        }

        // Check if recent OTP exists (rate limiting)
        if ($this->otpService->exists($formattedPhone)) {
            $remainingTime = $this->otpService->getRemainingTime($formattedPhone);

            return response()->json([
                'success' => false,
                'message' => "Please wait {$remainingTime} seconds before requesting a new OTP",
            ], 429);
        }

        // Generate OTP
        $code = $this->otpService->generate($formattedPhone);

        // Send SMS
        $sent = $this->HostPinnacleSmsService->sendOtp($formattedPhone, $code);

        if ($sent) {
            return response()->json([
                'success'      => true,
                'message'      => 'OTP sent successfully',
                'phone_number' => $formattedPhone,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP. Please try again.',
        ], 500);
    }

    /**
     * Login with OTP verification
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'code'         => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $phoneNumber = $this->formatPhoneNumber($request->phone_number);

        // Verify OTP
        if (! $this->otpService->verify($phoneNumber, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 401);
        }

        // Find or create user
        $user = User::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'name'              => 'User', // Will be updated during registration
                'phone_verified_at' => now(),
            ]
        );

        // Mark phone_number as verified if not already
        if (! $user->phone_verified_at) {
            $user->markPhoneAsVerified();
        }

        // Check if user is suspended
        if ($user->is_suspended) {
            if ($user->suspended_until && $user->suspended_until->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is suspended until ' . $user->suspended_until->format('Y-m-d H:i'),
                ], 403);
            } else {
                // Suspension expired, reactivate
                $user->update([
                    'is_suspended'    => false,
                    'suspended_until' => null,
                ]);
            }
        }

        // Generate token
        $token = $user->createToken('mobile-app')->plainTextToken;

        $isNewUser = ! $user->isRegistrationComplete();

        return response()->json([
            'success'     => true,
            'message'     => $isNewUser ? 'Account created. Please complete registration.' : 'Login successful',
            'token'       => $token,
            'user'        => $user->load('ward.constituency.county'),
            'is_new_user' => $isNewUser,
        ], 200);
    }

    /**
     * Verify OTP (alias for login)
     * POST /api/auth/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        return $this->login($request);
    }

    /**
     * Complete registration (for new users)
     * POST /api/auth/register
     * Requires: Bearer token
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'ward_id' => 'required|exists:wards,id',
            'bio'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check if already registered
        if ($user->isRegistrationComplete()) {
            return response()->json([
                'success' => false,
                'message' => 'User already registered',
            ], 400);
        }

        $user->update([
            'name'    => $request->name,
            'ward_id' => $request->ward_id,
            'bio'     => $request->bio,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration completed successfully',
            'user'    => $user->load('ward.constituency.county'),
        ], 200);
    }

    /**
     * Update user profile
     * PUT /api/auth/profile
     * Requires: Bearer token
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'sometimes|string|max:255',
            'ward_id' => 'sometimes|exists:wards,id',
            'bio'     => 'nullable|string|max:500',
            'email'   => 'nullable|email|unique:users,email,' . $request->user()->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->update($request->only(['name', 'ward_id', 'bio', 'email']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user'    => $user->fresh()->load('ward.constituency.county'),
        ], 200);
    }

    /**
     * Logout current user
     * POST /api/auth/logout
     * Requires: Bearer token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get current authenticated user
     * GET /api/auth/me
     * Requires: Bearer token
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('ward.constituency.county');

        return response()->json([
            'success' => true,
            'user'    => $user,
        ], 200);
    }

    /**
     * Refresh user profile data
     * GET /api/auth/refresh
     * Requires: Bearer token
     */
    public function refresh(Request $request)
    {
        $user = $request->user()->fresh()->load('ward.constituency.county');

        return response()->json([
            'success' => true,
            'user'    => $user,
        ], 200);
    }

    /**
     * Format phone_number number to Kenyan standard
     */
    private function formatPhoneNumber(string $phone_number): string
    {
        $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
        $phone_number = ltrim($phone_number, '+');

        if (str_starts_with($phone_number, '254')) {
            return $phone_number;
        } elseif (str_starts_with($phone_number, '0')) {
            return '254' . substr($phone_number, 1);
        } elseif (str_starts_with($phone_number, '7') || str_starts_with($phone_number, '1')) {
            return '254' . $phone_number;
        }

        return '254' . $phone_number;
    }
}
