<?php

use App\Models\Product;
use App\Models\RestaurantTable;

test('a guest can place an order by scanning a table QR code', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active', 'price' => 9.50]);

    $response = $this->postJson("/api/public/r/{$restaurant->slug}/orders", [
        'table_token' => $table->qr_token,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2, 'special_instructions' => 'No onions'],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'pending',
                'total_amount' => 19.0,
            ],
        ])
        ->assertJsonStructure(['data' => ['order_number', 'items']]);

    $this->assertDatabaseHas('orders', ['restaurant_id' => $restaurant->id, 'total_amount' => 19.0]);
    $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2, 'special_instructions' => 'No onions']);
});

test('ordering an inactive product is rejected', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'out_of_stock']);

    $response = $this->postJson("/api/public/r/{$restaurant->slug}/orders", [
        'table_token' => $table->qr_token,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $response->assertStatus(422);
});

test('ordering with another restaurants table token is rejected', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $foreignTable = RestaurantTable::factory()->create(['restaurant_id' => $otherRestaurant->id]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);

    $response = $this->postJson("/api/public/r/{$restaurant->slug}/orders", [
        'table_token' => $foreignTable->qr_token,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['table_token']);
});

test('ordering another restaurants product is rejected', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $foreignProduct = Product::factory()->create(['restaurant_id' => $otherRestaurant->id, 'status' => 'active']);

    $response = $this->postJson("/api/public/r/{$restaurant->slug}/orders", [
        'table_token' => $table->qr_token,
        'items' => [['product_id' => $foreignProduct->id, 'quantity' => 1]],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['items.0.product_id']);
});

test('order requires at least one item', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->postJson("/api/public/r/{$restaurant->slug}/orders", [
        'table_token' => $table->qr_token,
        'items' => [],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['items']);
});

test('a guest can poll order status using the public order number', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);

    $placeResponse = $this->postJson("/api/public/r/{$restaurant->slug}/orders", [
        'table_token' => $table->qr_token,
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $orderNumber = $placeResponse->json('data.order_number');

    $response = $this->getJson("/api/public/r/{$restaurant->slug}/orders/{$orderNumber}/status");

    $response->assertStatus(200)->assertJson(['data' => ['order_number' => $orderNumber, 'status' => 'pending']]);
});

test('an unknown order number returns 404', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();

    $response = $this->getJson("/api/public/r/{$restaurant->slug}/orders/ORD-DOESNOTEXIST/status");

    $response->assertStatus(404);
});
