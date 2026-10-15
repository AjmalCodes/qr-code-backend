<?php

namespace Database\Factories;

use App\Models\QrScan;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrScan>
 */
class QrScanFactory extends Factory
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
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
