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
    protected SmsService $smsService;
    protected OtpService $otpService;
    
    public function __construct(SmsService $smsService, OtpService $otpService)
    {
        $this->smsService = $smsService;
        $this->otpService = $otpService;
    }
    
    /**
     * Request OTP
     */
    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^(\+?254|0)?[17]\d{8}$/',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $phoneNumber = $request->phone_number;
        
        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($phoneNumber);
        
        // Validate phone number
        if (!$this->smsService->validatePhoneNumber($formattedPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Kenyan phone number',
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
        $sent = $this->smsService->sendOtp($formattedPhone, $code);
        
        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'phone_number' => $formattedPhone,
            ], 200);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP. Please try again.',
        ], 500);
    }
    
    /**
     * Verify OTP and login/register
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'code' => 'required|string|size:6',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $phoneNumber = $this->formatPhoneNumber($request->phone_number);
        
        // Verify OTP
        if (!$this->otpService->verify($phoneNumber, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 401);
        }
        
        // Find or create user
        $user = User::firstOrCreate(
            ['phone_number' => $phoneNumber],
            ['name' => 'User'] // Will be updated during registration
        );
        
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
                    'is_suspended' => false,
                    'suspended_until' => null,
                ]);
            }
        }
        
        // Generate token
        $token = $user->createToken('mobile-app')->plainTextToken;
        
        $isNewUser = $user->wasRecentlyCreated || !$user->ward_id;
        
        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user->load('ward.constituency.county'),
            'is_new_user' => $isNewUser,
        ], 200);
    }
    
    /**
     * Complete registration (for new users)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'ward_id' => 'required|exists:wards,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $user = $request->user();
        
        $user->update([
            'name' => $request->name,
            'ward_id' => $request->ward_id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Registration completed successfully',
            'user' => $user->load('ward.constituency.county'),
        ], 200);
    }
    
    /**
     * Logout
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
     * Get current user
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()->load('ward.constituency.county'),
        ], 200);
    }
    
    /**
     * Format phone number helper
     */
    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = ltrim($phone, '+');
        
        if (str_starts_with($phone, '254')) {
            return $phone;
        } elseif (str_starts_with($phone, '0')) {
            return '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            return '254' . $phone;
        }
        
        return '254' . $phone;
    }
}

