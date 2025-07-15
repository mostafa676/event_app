<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; 
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->phoneNumber(), 
            'password' => Hash::make('password'),  
            'role' => $this->faker->randomElement(['user', 'hall_owner', 'coordinator', 'admin']), 
            'image' => null,
            'dark_mode' => $this->faker->boolean(), 
            'language' => $this->faker->randomElement(['ar', 'en']),
            'remember_token' => Str::random(10),
        ];

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */

    }
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function hallOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'hall_owner',
        ]);
    }

    public function coordinator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'coordinator',
        ]);
    }

    public function regularUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }
}