<?php

use App\Models\Order;

test('a customer can list their own order history across restaurants', function () {
    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    ['restaurant' => $restaurantA] = createOwnerWithRestaurant();
    ['restaurant' => $restaurantB] = createOwnerWithRestaurant();

    Order::factory()->create(['customer_id' => $customer->id, 'restaurant_id' => $restaurantA->id]);
    Order::factory()->create(['customer_id' => $customer->id, 'restaurant_id' => $restaurantB->id]);
    Order::factory()->create(['customer_id' => null]); // guest order, unrelated

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/customer/orders');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => ['*' => ['id', 'order_number', 'restaurant', 'status', 'total_amount']]]);
});

test('a customer only sees their own orders, not another customers', function () {
    ['token' => $token] = createVerifiedCustomer();
    ['customer' => $otherCustomer] = createVerifiedCustomer();
    Order::factory()->create(['customer_id' => $otherCustomer->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/customer/orders');

    $response->assertStatus(200)->assertJsonCount(0, 'data');
});

test('a customer can view a single order of theirs', function () {
    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/orders/{$order->id}");

    $response->assertStatus(200)->assertJson(['data' => ['id' => $order->id]]);
});

test('a customer cannot view another customers order', function () {
    ['token' => $token] = createVerifiedCustomer();
    ['customer' => $otherCustomer] = createVerifiedCustomer();
    $foreignOrder = Order::factory()->create(['customer_id' => $otherCustomer->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/orders/{$foreignOrder->id}");

    $response->assertStatus(404);
});

test('order history requires customer authentication', function () {
    $response = $this->getJson('/api/customer/orders');

    $response->assertStatus(401);
});
