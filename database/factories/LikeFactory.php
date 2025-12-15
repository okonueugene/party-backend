<?php

namespace Database\Factories;

use App\Models\Like;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Like>
 */
class LikeFactory extends Factory
{
    protected $model = Like::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default to liking a post
        $post = Post::inRandomOrder()->first();
        
        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'likeable_id' => $post?->id ?? 1,
            'likeable_type' => Post::class,
        ];
    }

    /**
     * Indicate that the like is for a post.
     */
    public function forPost(Post $post = null): static
    {
        return $this->state(function (array $attributes) use ($post) {
            $targetPost = $post ?? Post::inRandomOrder()->first();
            return [
                'likeable_id' => $targetPost?->id ?? 1,
                'likeable_type' => Post::class,
            ];
        });
    }

    /**
     * Indicate that the like is for a comment.
     */
    public function forComment(Comment $comment = null): static
    {
        return $this->state(function (array $attributes) use ($comment) {
            $targetComment = $comment ?? Comment::inRandomOrder()->first();
            return [
                'likeable_id' => $targetComment?->id ?? 1,
                'likeable_type' => Comment::class,
            ];
        });
    }
}

