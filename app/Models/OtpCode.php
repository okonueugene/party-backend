<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'phone_number',
        'code',
        'expires_at',
        'verified',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];

    /**
     * Scope for active (non-expired, non-verified) OTPs
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
                    ->where('verified', false);
    }

    /**
     * Scope for expired OTPs
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if this OTP is still valid
     */
    public function isValid(): bool
    {
        return !$this->verified && $this->expires_at->isFuture();
    }
}
