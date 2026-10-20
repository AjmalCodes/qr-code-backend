<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Customer;
use App\Models\LoyaltyClaim;
use App\Models\LoyaltyOffer;
use App\Models\LoyaltyProgress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\QrScan;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoDataSeeder extends Seeder
{
    /**
     * Populates every tenant-facing table with ~20 restaurants' worth of
     * realistic, interlinked demo data (menus, tables, staff, loyalty,
     * subscriptions, QR scans, and ~20 orders per restaurant spread across
     * the last year) so the owner dashboard, reports, and analytics
     * endpoints all have something meaningful to show.
     */
    public function run(): void
    {
        $customers = Customer::factory()->count(20)->create();

        Restaurant::factory()
            ->count(20)
            ->create()
            ->each(fn (Restaurant $restaurant) => $this->seedRestaurant($restaurant, $customers));
    }

    private function seedRestaurant(Restaurant $restaurant, Collection $customers): void
    {
        $categories = Category::factory()->count(4)->create(['restaurant_id' => $restaurant->id]);

        $products = $categories->flatMap(fn (Category $category) => Product::factory()->count(2)->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
        ]));

        // Explicit "Table 1".."Table 5" labels (not the factory's random
        // default) so QR scan/order breakdowns read clearly per table.
        $tables = collect(range(1, 5))->map(fn (int $number) => RestaurantTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'label' => "Table {$number}",
        ]));

        User::factory()->count(2)->create([
            'role' => UserRole::Waiter->value,
            'restaurant_id' => $restaurant->id,
        ]);

        $offers = LoyaltyOffer::factory()->count(2)->create([
            'restaurant_id' => $restaurant->id,
            'product_id' => fn () => $products->random()->id,
        ]);
        $offers->last()->update(['is_active' => false]);

        Subscription::factory()->create([
            'restaurant_id' => $restaurant->id,
            'status' => fake()->randomElement(['trial', 'active', 'active', 'expired']),
        ]);

        foreach ($tables as $table) {
            QrScan::factory()->count(fake()->numberBetween(2, 8))->create([
                'restaurant_id' => $restaurant->id,
                'restaurant_table_id' => $table->id,
                'created_at' => fake()->dateTimeBetween('-11 months', 'now'),
            ]);
        }

        $regulars = $customers->random(5);

        for ($i = 0; $i < 20; $i++) {
            $customer = fake()->boolean(70) ? $regulars->random() : null;
            $createdAt = fake()->dateTimeBetween('-11 months', 'now');

            $order = Order::factory()->create([
                'restaurant_id' => $restaurant->id,
                'restaurant_table_id' => $tables->random()->id,
                'customer_id' => $customer?->id,
                'status' => fake()->randomElement(['pending', 'preparing', 'served', 'completed']),
                'total_amount' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $total = 0;

            foreach ($products->random(fake()->numberBetween(1, 3)) as $product) {
                $quantity = fake()->numberBetween(1, 3);

                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                ]);

                $total += $product->price * $quantity;
            }

            $order->update(['total_amount' => $total]);

            if ($customer) {
                LoyaltyProgress::query()->updateOrCreate(
                    ['customer_id' => $customer->id, 'loyalty_offer_id' => $offers->first()->id],
                    ['progress_count' => fake()->numberBetween(0, 3)],
                );
            }
        }

        LoyaltyClaim::factory()->count(2)->create([
            'restaurant_id' => $restaurant->id,
            'loyalty_offer_id' => $offers->first()->id,
            'customer_id' => fn () => $regulars->random()->id,
        ]);
    }
}
