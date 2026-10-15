<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;

test('super admin can list restaurant owners with their restaurant nested', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    ['restaurant' => $restaurant, 'user' => $owner] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurant-users');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson([
            'data' => [
                [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'restaurants' => [
                        ['id' => $restaurant->id, 'name' => $restaurant->name, 'slug' => $restaurant->slug],
                    ],
                ],
            ],
        ]);
});

test('a user linked to five restaurants appears once, not five times', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    ['user' => $owner] = createOwnerWithRestaurant();
    // Simulate one owner ending up linked to several restaurants — the DB
    // allows this (restaurants.user_id has no unique constraint) even though
    // today's creation flow never produces it on its own.
    Restaurant::factory()->count(4)->create(['user_id' => $owner->id]);

    $this->assertDatabaseCount('restaurants', 5);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurant-users');

    $response->assertStatus(200)->assertJsonCount(1, 'data');
    expect($response->json('data.0.restaurants'))->toHaveCount(5);
});

test('restaurant owner list only includes restaurant-role users', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    User::factory()->create(['role' => UserRole::Waiter->value]);
    User::factory()->create(['role' => UserRole::User->value]);
    createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurant-users');

    $response->assertStatus(200)->assertJsonCount(1, 'data');
});

test('restaurant owners can be searched by their own name', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    createOwnerWithRestaurant('password123', ['name' => 'Jordan Match']);
    createOwnerWithRestaurant('password123', ['name' => 'Someone Else']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurant-users?search=Jordan');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [['name' => 'Jordan Match']]]);
});

test('restaurant owners can be searched by their restaurants name', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    createOwnerWithRestaurant('password123', [], ['name' => 'Pizza Palace']);
    createOwnerWithRestaurant('password123', [], ['name' => 'Burger Barn']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurant-users?search=Pizza');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [['restaurants' => [['name' => 'Pizza Palace']]]]]);
});

test('restaurant owner list is paginated', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    createOwnerWithRestaurant();
    createOwnerWithRestaurant();
    createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurant-users?per_page=2');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJson(['meta' => ['total' => 3, 'per_page' => 2]]);
});

test('non super admin cannot list restaurant owners', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurant-users');

    $response->assertStatus(403);
});

test('unauthenticated requests cannot list restaurant owners', function () {
    $response = $this->getJson('/api/restaurant-users');

    $response->assertStatus(401);
});
