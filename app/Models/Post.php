<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasGeographicLocation;

class Post extends Model
{
    use HasFactory, SoftDeletes, HasGeographicLocation;

    protected $fillable = [
        'user_id',
        'content',
        'image',
        'audio',
        'county_id',
        'constituency_id',
        'ward_id',
        'likes_count',
        'comments_count',
        'shares_count',
        'flags_count',
        'is_flagged',
        'is_active',
    ];

    protected $casts = [
        'is_flagged' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    /**
     * Get all comments including nested ones.
     */
    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the likes for the post.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Get the flags for the post.
     */
    public function flags(): MorphMany
    {
        return $this->morphMany(Flag::class, 'flaggable');
    }

    /**
     * Get the shares for the post.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }
}

