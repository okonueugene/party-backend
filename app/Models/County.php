<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class County extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Get the constituencies for the county.
     */
    public function constituencies(): HasMany
    {
        return $this->hasMany(Constituency::class);
    }

    /**
     * Get all wards for the county through constituencies.
     */
    public function wards(): HasManyThrough
    {
        return $this->hasManyThrough(Ward::class, Constituency::class);
    }

    /**
     * Get the users in this county.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the posts in this county.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
