<?php

use App\Enums\UserRole;
use App\Models\User;

test('a waiter can log in to the staff panel', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['user' => $waiter] = createWaiterForRestaurant($restaurant, 'password123');

    $response = $this->postJson('/api/waiter/login', [
        'email' => $waiter->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => ['user' => ['role' => 'waiter']]]);
});

test('the restaurant owner can also log in to the staff panel', function () {
    ['user' => $owner] = createOwnerWithRestaurant('password123');

    $response = $this->postJson('/api/waiter/login', [
        'email' => $owner->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200);
});

test('a regular user cannot log in to the staff panel', function () {
    $user = User::factory()->create(['role' => UserRole::User->value, 'password' => 'password123']);

    $response = $this->postJson('/api/waiter/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403);
});

test('waiter routes require staff authentication', function () {
    $response = $this->getJson('/api/waiter/orders');

    $response->assertStatus(401);
});

test('a customer token cannot access the waiter panel', function () {
    ['token' => $token] = createVerifiedCustomer();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/waiter/orders');

    $response->assertStatus(403);
});
