<?php

namespace Database\Factories;

use App\Models\County;
use App\Models\Constituency;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random ward and its associated constituency and county
        $ward = Ward::inRandomOrder()->first();
        $constituency = $ward?->constituency;
        $county = $constituency?->county;

        return [
            'name' => fake()->name(),
            'phone' => '254' . fake()->unique()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'otp' => null,
            'otp_expires_at' => null,
            'county_id' => $county?->id,
            'constituency_id' => $constituency?->id,
            'ward_id' => $ward?->id,
            'profile_image' => fake()->boolean(30) ? 'avatars/' . fake()->uuid() . '.jpg' : null,
            'bio' => fake()->boolean(50) ? fake()->sentence(10) : null,
            'is_active' => fake()->boolean(95),
            'phone_verified_at' => fake()->boolean(80) ? now() : null,
        ];
    }

    /**
     * Indicate that the user is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
