<?php

use App\Models\Customer;
use App\Models\Order;

test('owner can list customers who have ordered from their restaurant', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $customer = Customer::factory()->create(['name' => 'Casey Customer']);

    Order::factory()->create(['restaurant_id' => $restaurant->id, 'customer_id' => $customer->id, 'total_amount' => 20]);
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'customer_id' => $customer->id, 'total_amount' => 30]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/customers');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson([
            'data' => [
                [
                    'id' => $customer->id,
                    'name' => 'Casey Customer',
                    'orders_count' => 2,
                    'total_spent' => 50.0,
                ],
            ],
        ]);
});

test('guest orders without a customer account do not appear in the customer list', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'customer_id' => null]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/customers');

    $response->assertStatus(200)->assertJsonCount(0, 'data');
});

test('owner only sees customers who ordered from their own restaurant', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $customer = Customer::factory()->create();
    Order::factory()->create(['restaurant_id' => $otherRestaurant->id, 'customer_id' => $customer->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/customers');

    $response->assertStatus(200)->assertJsonCount(0, 'data');
});

test('a customer who ordered from two restaurants shows separate totals for each', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $customer = Customer::factory()->create();

    Order::factory()->create(['restaurant_id' => $restaurant->id, 'customer_id' => $customer->id, 'total_amount' => 10]);
    Order::factory()->create(['restaurant_id' => $otherRestaurant->id, 'customer_id' => $customer->id, 'total_amount' => 999]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/customers');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [['orders_count' => 1, 'total_spent' => 10.0]]]);
});
