<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use App\Models\County;
use App\Models\Constituency;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasImage = $this->faker->boolean(30);
        $hasAudio = !$hasImage && $this->faker->boolean(20);

        // Get a random ward and its associated constituency and county
        $ward = Ward::inRandomOrder()->first();
        $constituency = $ward?->constituency;
        $county = $constituency?->county;

        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'content' => $this->faker->paragraphs(rand(1, 3), true),
            'image' => $hasImage ? 'posts/' . $this->faker->uuid() . '.jpg' : null,
            'audio' => $hasAudio ? 'posts/' . $this->faker->uuid() . '.mp3' : null,
            'county_id' => $county?->id,
            'constituency_id' => $constituency?->id,
            'ward_id' => $ward?->id,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
            'flags_count' => 0,
            'is_flagged' => false,
            'is_active' => $this->faker->boolean(95),
        ];
    }

    /**
     * Indicate that the post has an image.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image' => 'posts/' . $this->faker->uuid() . '.jpg',
            'audio' => null,
        ]);
    }

    /**
     * Indicate that the post has audio.
     */
    public function withAudio(): static
    {
        return $this->state(fn (array $attributes) => [
            'image' => null,
            'audio' => 'posts/' . $this->faker->uuid() . '.mp3',
        ]);
    }

    /**
     * Indicate that the post is flagged.
     */
    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_flagged' => true,
            'flags_count' => $this->faker->numberBetween(3, 10),
        ]);
    }

    /**
     * Indicate that the post is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
