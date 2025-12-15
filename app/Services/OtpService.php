<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected HostPinnacleSmsService $smsService;

    public function __construct(HostPinnacleSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Generate and send OTP to user.
     *
     * @param User $user
     * @return array
     */
    public function generateAndSendOtp(User $user): array
    {
        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Set expiration time (5 minutes from now)
        $expiresAt = Carbon::now()->addMinutes(5);

        // Update user with OTP
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => $expiresAt,
        ]);

        // Send OTP via SMS
        $message = "Your verification code is: {$otp}. Valid for 5 minutes.";
        $smsResult = $this->smsService->sendSms($user->phone, $message);

        if (!$smsResult['success']) {
            Log::warning('OTP generated but SMS failed to send', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);
        }

        return [
            'success' => true,
            'otp' => $otp, // In production, you might want to hide this
            'expires_at' => $expiresAt,
            'sms_sent' => $smsResult['success'],
        ];
    }

    /**
     * Verify OTP for user.
     *
     * @param User $user
     * @param string $otp
     * @return array
     */
    public function verifyOtp(User $user, string $otp): array
    {
        // Check if OTP exists
        if (!$user->otp) {
            return [
                'success' => false,
                'message' => 'No OTP found. Please request a new one.',
            ];
        }

        // Check if OTP is expired
        if ($user->otp_expires_at && Carbon::now()->isAfter($user->otp_expires_at)) {
            return [
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ];
        }

        // Verify OTP
        if ($user->otp !== $otp) {
            return [
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ];
        }

        // OTP is valid - clear it and verify phone
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'phone_verified_at' => Carbon::now(),
        ]);

        return [
            'success' => true,
            'message' => 'OTP verified successfully.',
        ];
    }

    /**
     * Resend OTP to user.
     *
     * @param User $user
     * @return array
     */
    public function resendOtp(User $user): array
    {
        return $this->generateAndSendOtp($user);
    }
}

