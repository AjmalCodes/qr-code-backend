<?php

use App\Models\Product;
use App\Models\QrScan;
use App\Models\RestaurantTable;

test('loading the public menu with a valid table token records a scan', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    $this->getJson("/api/public/r/{$restaurant->slug}/menu?table={$table->qr_token}")->assertStatus(200);

    $this->assertDatabaseHas('qr_scans', [
        'restaurant_id' => $restaurant->id,
        'restaurant_table_id' => $table->id,
    ]);
});

test('loading the public menu without a table token records no scan', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();

    $this->getJson("/api/public/r/{$restaurant->slug}/menu")->assertStatus(200);

    $this->assertDatabaseCount('qr_scans', 0);
});

test('a table token from another restaurant does not record a scan', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $foreignTable = RestaurantTable::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $this->getJson("/api/public/r/{$restaurant->slug}/menu?table={$foreignTable->qr_token}")->assertStatus(200);

    $this->assertDatabaseCount('qr_scans', 0);
});

test('owner can view per-table qr analytics with conversion rate', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);

    // 4 scans, 1 resulting order => 25% conversion
    collect(range(1, 4))->each(fn () => $this->getJson("/api/public/r/{$restaurant->slug}/menu?table={$table->qr_token}"));

    $this->postJson("/api/public/r/{$restaurant->slug}/orders", [
        'table_token' => $table->qr_token,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertStatus(201);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/analytics/qr');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'total_scans' => 4,
                'total_orders' => 1,
                'conversion_rate' => 25.0,
                'tables' => [
                    ['table_id' => $table->id, 'scans' => 4, 'orders' => 1, 'conversion_rate' => 25.0],
                ],
            ],
        ]);
});

test('owner qr analytics only reflects their own restaurant', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $foreignTable = RestaurantTable::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $this->getJson("/api/public/r/{$otherRestaurant->slug}/menu?table={$foreignTable->qr_token}");

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/analytics/qr');

    $response->assertStatus(200)->assertJson(['data' => ['total_scans' => 0]]);
});

test('owner can filter qr analytics by date range', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    QrScan::factory()->create([
        'restaurant_id' => $restaurant->id,
        'restaurant_table_id' => $table->id,
        'created_at' => now()->subDays(10),
    ]);
    QrScan::factory()->create([
        'restaurant_id' => $restaurant->id,
        'restaurant_table_id' => $table->id,
        'created_at' => now(),
    ]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/analytics/qr?from='.now()->subDay()->toDateString());

    $response->assertStatus(200)->assertJson(['data' => ['total_scans' => 1]]);
});
