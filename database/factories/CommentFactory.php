<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::inRandomOrder()->first()?->id ?? Post::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'content' => $this->faker->sentences(rand(1, 3), true),
            'parent_id' => null,
            'likes_count' => 0,
            'is_active' => $this->faker->boolean(95),
        ];
    }

    /**
     * Indicate that this is a reply to another comment.
     */
    public function reply(Comment $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            if ($parent) {
                return [
                    'parent_id' => $parent->id,
                    'post_id' => $parent->post_id,
                ];
            }
            
            $parentComment = Comment::whereNull('parent_id')->inRandomOrder()->first();
            return [
                'parent_id' => $parentComment?->id,
                'post_id' => $parentComment?->post_id ?? $attributes['post_id'],
            ];
        });
    }

    /**
     * Indicate that the comment is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

