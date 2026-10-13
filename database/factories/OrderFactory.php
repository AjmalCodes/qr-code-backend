<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
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
            'restaurant_table_id' => RestaurantTable::factory(),
            'customer_id' => null,
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'status' => OrderStatus::Pending->value,
            'total_amount' => $this->faker->randomFloat(2, 5, 150),
        ];
    }

    public function preparing(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Preparing->value]);
    }

    public function served(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Served->value]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Completed->value]);
    }
}
