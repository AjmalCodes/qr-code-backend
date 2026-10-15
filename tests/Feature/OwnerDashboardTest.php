<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Subscription;

test('owner can view their dashboard stats', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    Category::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);
    Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'out_of_stock']);
    RestaurantTable::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'pending', 'total_amount' => 20]);
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'completed', 'total_amount' => 30]);
    Subscription::factory()->create(['restaurant_id' => $restaurant->id, 'plan_name' => 'Pro']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/dashboard/stats');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'orders' => ['total', 'today', 'pending', 'preparing', 'completed'],
                'revenue' => ['total', 'today'],
                'menu' => ['categories', 'products', 'active_products', 'out_of_stock_products'],
                'tables',
                'qr_scans_total',
                'subscription' => ['plan_name', 'status', 'remaining_days'],
            ],
        ])
        ->assertJson([
            'data' => [
                'orders' => ['total' => 2, 'pending' => 1, 'completed' => 1],
                'revenue' => ['total' => 50.0],
                'menu' => ['categories' => 2, 'products' => 2, 'active_products' => 1, 'out_of_stock_products' => 1],
                'tables' => 3,
                'subscription' => ['plan_name' => 'Pro'],
            ],
        ]);
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
