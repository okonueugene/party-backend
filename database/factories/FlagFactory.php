<?php

namespace Database\Factories;

use App\Models\Flag;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flag>
 */
class FlagFactory extends Factory
{
    protected $model = Flag::class;

    /**
     * Flag reasons.
     */
    protected array $reasons = [
        'spam',
        'harassment',
        'hate_speech',
        'violence',
        'misinformation',
        'inappropriate_content',
        'copyright_violation',
        'other',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default to flagging a post
        $post = Post::inRandomOrder()->first();
        
        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'flaggable_id' => $post?->id ?? 1,
            'flaggable_type' => Post::class,
            'reason' => $this->faker->randomElement($this->reasons),
            'description' => $this->faker->boolean(70) ? $this->faker->sentence() : null,
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    /**
     * Indicate that the flag is for a post.
     */
    public function forPost(Post $post = null): static
    {
        return $this->state(function (array $attributes) use ($post) {
            $targetPost = $post ?? Post::inRandomOrder()->first();
            return [
                'flaggable_id' => $targetPost?->id ?? 1,
                'flaggable_type' => Post::class,
            ];
        });
    }

    /**
     * Indicate that the flag is for a comment.
     */
    public function forComment(Comment $comment = null): static
    {
        return $this->state(function (array $attributes) use ($comment) {
            $targetComment = $comment ?? Comment::inRandomOrder()->first();
            return [
                'flaggable_id' => $targetComment?->id ?? 1,
                'flaggable_type' => Comment::class,
            ];
        });
    }

    /**
     * Indicate that the flag has been reviewed.
     */
    public function reviewed(string $status = 'resolved'): static
    {
        return $this->state(function (array $attributes) use ($status) {
            $admin = AdminUser::inRandomOrder()->first();
            return [
                'status' => $status,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the flag is pending review.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }
}

