<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_identifier' => fake()->unique()->numerify('115300####'),
            'user_full_name' => fake()->name(),
            'user_password' => static::$password ??= Hash::make('password'),
            'user_status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_status' => false,
        ]);
    }
}
