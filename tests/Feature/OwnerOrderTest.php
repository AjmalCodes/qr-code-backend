<?php

use App\Models\Order;

test('owner can list orders for their restaurant', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    Order::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);

    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/orders');

    $response->assertStatus(200)->assertJsonCount(2, 'data');
});

test('owner can filter orders by status', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'pending']);
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'completed']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/orders?status=completed');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [['status' => 'completed']]]);
});

test('owner can filter orders by date range', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'created_at' => now()->subDays(10)]);
    $recent = Order::factory()->create(['restaurant_id' => $restaurant->id, 'created_at' => now()]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/orders?from='.now()->subDay()->toDateString());

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [['id' => $recent->id]]]);
});

test('owner can view a single order', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/orders/{$order->id}");

    $response->assertStatus(200)->assertJson(['data' => ['id' => $order->id]]);
});

test('owner cannot view another restaurants order', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $foreignOrder = Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/orders/{$foreignOrder->id}");

    $response->assertStatus(404);
});
