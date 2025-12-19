<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'phone_number',
        'name',
        'email',
        'password',
        'ward_id',
        'profile_image',
        'bio',
        'is_admin',
        'is_suspended',
        'suspended_until',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'suspended_until'   => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_suspended'      => 'boolean',
        ];
    }

    /**
     * Get the ward that the user belongs to.
     */
    public function constituency(): BelongsTo 
{
    return $this->belongsTo(Constituency::class);
}

// The county relationship must also be a direct BelongsTo.
public function county(): BelongsTo 
{
    return $this->belongsTo(County::class);
}

/**
 * Get the ward that the user belongs to (this one is already correct).
 */
public function ward(): BelongsTo
{
    return $this->belongsTo(Ward::class);
}
    /**
     * Get the posts created by the user.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the comments created by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the likes created by the user.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Get the flags created by the user.
     */
    public function flags(): HasMany
    {
        return $this->hasMany(Flag::class);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_suspended', false)
            ->where(function ($q) {
                $q->whereNull('suspended_until')
                    ->orWhere('suspended_until', '<', now());
            });
    }

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope for verified users
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('phone_verified_at');
    }

    /**
     * Check if user has completed registration
     */
    public function isRegistrationComplete(): bool
    {
        return $this->ward_id !== null && $this->name !== 'User';
    }

    /**
     * Mark phone_number as verified
     */
    public function markPhoneAsVerified(): void
    {
        $this->phone_verified_at = now();
        $this->save();
    }

    // Relationship to OTPs (one-to-many via phone_number)
    public function otps()
    {
        return $this->hasMany(OtpCode::class, 'phone_number', 'phone_number');
    }

    // Helper to get the latest active OTP
    public function getLatestOtp()
    {
        return $this->otps()->where('expires_at', '>', now())
            ->where('verified', false)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
