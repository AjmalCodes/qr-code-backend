<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => $this->faker->words(2, true),
            'unit_price' => $this->faker->randomFloat(2, 1, 50),
            'quantity' => $this->faker->numberBetween(1, 5),
            'special_instructions' => $this->faker->optional()->sentence(),
        ];
    }
}
