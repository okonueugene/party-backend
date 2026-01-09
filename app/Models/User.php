<?php
namespace App\Models;

use App\Enums\AdminRole;
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
        'admin_role',
        'permissions',
        'is_suspended',
        'suspended_until',
        'phone_verified_at',
        'last_login_at',
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
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_suspended'      => 'boolean',
            'permissions'       => 'array',
            'admin_role'        => AdminRole::class, // Cast to enum
        ];
    }

    // ============ RELATIONSHIPS ============

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function flags()
    {
        return $this->hasMany(Flag::class);
    }

    // ============ ADMIN ROLE METHODS ============

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin && $this->admin_role === AdminRole::SUPER_ADMIN;
    }

    /**
     * Check if user is any type of admin
     */
    public function isAnyAdmin(): bool
    {
        return $this->is_admin && $this->admin_role !== null;
    }

    /**
     * Check if user has a specific admin role
     */
    public function hasRole(AdminRole $role): bool
    {
        return $this->admin_role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->admin_role, $roles);
    }

    /**
     * Get user's role label
     */
    public function getRoleLabel(): ?string
    {
        return $this->admin_role?->label();
    }

    // ============ PERMISSION METHODS ============

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string | Permission $permission): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissionString = $permission instanceof Permission
            ? $permission->value
            : $permission;

        return in_array($permissionString, $this->permissions ?? []);
    }

    /**
     * Check if user has all given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Grant a permission to the user
     */
    public function grantPermission(string | Permission $permission): void
    {
        $permissionString = $permission instanceof Permission
            ? $permission->value
            : $permission;

        $permissions = $this->permissions ?? [];

        if (! in_array($permissionString, $permissions)) {
            $permissions[] = $permissionString;
            $this->update(['permissions' => $permissions]);
        }
    }

    /**
     * Revoke a permission from the user
     */
    public function revokePermission(string | Permission $permission): void
    {
        $permissionString = $permission instanceof Permission
            ? $permission->value
            : $permission;

        $permissions = $this->permissions ?? [];
        $permissions = array_diff($permissions, [$permissionString]);

        $this->update(['permissions' => array_values($permissions)]);
    }

    /**
     * Sync user permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->update(['permissions' => $permissions]);
    }

    /**
     * Set user role and default permissions
     * FIXED: Accept AdminRole enum
     */
    public function assignRole(AdminRole $role): void
    {
        $this->update([
            'is_admin'    => true,
            'admin_role'  => $role, // Laravel will automatically handle enum casting
            'permissions' => $role->defaultPermissions(),
        ]);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    // ============ SCOPES ============

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true)->whereNotNull('admin_role');
    }

    /**
     * Scope for specific admin role
     */
    public function scopeRole($query, AdminRole $role)
    {
        return $query->where('admin_role', $role);
    }

    /**
     * Scope for super admins
     */
    public function scopeSuperAdmins($query)
    {
        return $query->where('admin_role', AdminRole::SUPER_ADMIN);
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
     * Mark phone as verified
     */
    public function markPhoneAsVerified(): void
    {
        $this->phone_verified_at = now();
        $this->save();
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
