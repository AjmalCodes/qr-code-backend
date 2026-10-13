<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'category_id' => Category::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'image' => null,
            'status' => ProductStatus::Active->value,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::OutOfStock->value]);
    }
}
