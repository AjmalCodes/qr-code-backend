<?php

use App\Models\Order;

test('waiter can list orders for their restaurant', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);

    Order::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);

    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/waiter/orders');

    $response->assertStatus(200)->assertJsonCount(2, 'data');
});

test('waiter can filter orders by status', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);

    Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'pending']);
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'served']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/waiter/orders?status=pending');

    $response->assertStatus(200)->assertJsonCount(1, 'data');
});

test('waiter can advance an order from pending to preparing', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);
    $order = Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'pending']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/waiter/orders/{$order->id}/status", ['status' => 'preparing']);

    $response->assertStatus(200)->assertJson(['data' => ['status' => 'preparing']]);
});

test('waiter cannot skip the order status workflow', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);
    $order = Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'pending']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/waiter/orders/{$order->id}/status", ['status' => 'completed']);

    $response->assertStatus(422);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
});

test('waiter cannot update another restaurants order', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);

    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $foreignOrder = Order::factory()->create(['restaurant_id' => $otherRestaurant->id, 'status' => 'pending']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/waiter/orders/{$foreignOrder->id}/status", ['status' => 'preparing']);

    $response->assertStatus(404);
});

test('an invalid status value is rejected', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);
    $order = Order::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'pending']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/waiter/orders/{$order->id}/status", ['status' => 'bogus']);

    $response->assertStatus(422);
});
