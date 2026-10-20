<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\QrScan;
use App\Models\RestaurantTable;
use App\Models\Subscription;

test('owner can view their dashboard stats', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    Category::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);
    Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'out_of_stock']);
    $tables = RestaurantTable::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);
    $customer = Customer::factory()->create();

    Order::factory()->create(['restaurant_id' => $restaurant->id, 'restaurant_table_id' => $tables[0]->id, 'customer_id' => $customer->id, 'status' => 'pending', 'total_amount' => 20]);
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'restaurant_table_id' => $tables[1]->id, 'status' => 'completed', 'total_amount' => 30]);
    Subscription::factory()->create(['restaurant_id' => $restaurant->id, 'plan_name' => 'Pro']);

    QrScan::factory()->count(2)->create(['restaurant_id' => $restaurant->id, 'restaurant_table_id' => $tables[0]->id]);
    QrScan::factory()->create(['restaurant_id' => $restaurant->id, 'restaurant_table_id' => $tables[1]->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/dashboard/stats');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'orders' => ['total', 'today', 'pending', 'preparing', 'completed'],
                'revenue' => ['total', 'today'],
                'menu' => ['categories', 'products', 'active_products', 'out_of_stock_products'],
                'tables',
                'qr_scans' => [
                    'total',
                    'by_table' => [
                        '*' => ['table_id', 'label', 'scans', 'orders', 'conversion_rate'],
                    ],
                ],
                'total_customers',
                'subscription' => ['plan_name', 'status', 'remaining_days'],
                'monthly_stats' => [
                    '*' => ['year', 'month', 'label', 'orders', 'revenue', 'scans', 'customers'],
                ],
                'recent_orders' => [
                    '*' => ['id', 'order_number', 'table', 'status', 'total_amount', 'items', 'created_at'],
                ],
            ],
        ])
        ->assertJson([
            'data' => [
                'orders' => ['total' => 2, 'pending' => 1, 'completed' => 1],
                'revenue' => ['total' => 50.0],
                'menu' => ['categories' => 2, 'products' => 2, 'active_products' => 1, 'out_of_stock_products' => 1],
                'tables' => 3,
                'qr_scans' => ['total' => 3],
                'total_customers' => 1,
                'subscription' => ['plan_name' => 'Pro'],
            ],
        ]);

    $response->assertJsonCount(12, 'data.monthly_stats');
    $response->assertJsonCount(2, 'data.recent_orders');

    $byTable = collect($response->json('data.qr_scans.by_table'))->keyBy('table_id');
    expect($byTable->get($tables[0]->id)['scans'])->toBe(2);
    expect($byTable->get($tables[1]->id)['scans'])->toBe(1);

    $currentMonth = collect($response->json('data.monthly_stats'))->last();
    expect($currentMonth['orders'])->toBe(2);
    expect($currentMonth['scans'])->toBe(3);
    expect($currentMonth['customers'])->toBe(1);
});

test('owner dashboard only reflects their own restaurant data', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();

    Order::factory()->count(5)->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/dashboard/stats');

    $response->assertStatus(200)->assertJson(['data' => ['orders' => ['total' => 0]]]);
});

test('owner dashboard shows null subscription when none exists', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/dashboard/stats');

    $response->assertStatus(200)->assertJson(['data' => ['subscription' => null]]);
});

test('non owner cannot access the owner dashboard', function () {
    $response = $this->getJson('/api/owner/dashboard/stats');

    $response->assertStatus(401);
});
