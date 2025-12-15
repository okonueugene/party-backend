<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the flags reviewed by this admin.
     */
    public function reviewedFlags(): HasMany
    {
        return $this->hasMany(Flag::class, 'reviewed_by');
    }

    /**
     * Check if the admin user is an admin (not just a moderator).
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

