<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

