<?php

namespace Database\Factories;

use App\Enums\RestaurantStatus;
use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Restaurant->value,
        ]);

        $name = $this->faker->unique()->company();

        return [
            'user_id' => $user->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 999999),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'currency' => 'USD',
            'status' => RestaurantStatus::Active->value,
            'temporary_password' => Str::random(12),
        ];
    }
}
