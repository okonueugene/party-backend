<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate OTP code
     *
     * @param string $phoneNumber
     * @return string
     */
    public function generate(string $phoneNumber): string
    {
        // Delete old OTPs for this number
        OtpCode::where('phone_number', $phoneNumber)->delete();
        
        // Generate 6-digit code
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store in database
        OtpCode::create([
            'phone_number' => $phoneNumber,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);
        
        return $code;
    }
    
    /**
     * Verify OTP code
     *
     * @param string $phoneNumber
     * @param string $code
     * @return bool
     */
    public function verify(string $phoneNumber, string $code): bool
    {
        $otp = OtpCode::where('phone_number', $phoneNumber)
                     ->where('code', $code)
                     ->where('expires_at', '>', now())
                     ->where('verified', false)
                     ->first();
        
        if ($otp) {
            $otp->update(['verified' => true]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if OTP exists and is valid
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function exists(string $phoneNumber): bool
    {
        return OtpCode::where('phone_number', $phoneNumber)
                     ->where('expires_at', '>', now())
                     ->where('verified', false)
                     ->exists();
    }
    
    /**
     * Get remaining time in seconds for OTP
     *
     * @param string $phoneNumber
     * @return int|null
     */
    public function getRemainingTime(string $phoneNumber): ?int
    {
        $otp = OtpCode::where('phone_number', $phoneNumber)
                     ->where('expires_at', '>', now())
                     ->where('verified', false)
                     ->first();
        
        if ($otp) {
            return Carbon::now()->diffInSeconds($otp->expires_at);
        }
        
        return null;
    }
    
    /**
     * Clean up expired OTPs
     *
     * @return int Number of deleted records
     */
    public function cleanup(): int
    {
        return OtpCode::where('expires_at', '<', now())->delete();
    }
}

