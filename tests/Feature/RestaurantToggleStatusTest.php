<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;

test('super admin can disable a restaurant', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $restaurant = Restaurant::factory()->create(['status' => 'active']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/restaurants/{$restaurant->id}/toggle-status");

    $response->assertStatus(200)
        ->assertJson(['data' => ['status' => 'inactive']]);

    $this->assertDatabaseHas('restaurants', ['id' => $restaurant->id, 'status' => 'inactive']);
});

test('super admin can re-enable a disabled restaurant', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $restaurant = Restaurant::factory()->create(['status' => 'inactive']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/restaurants/{$restaurant->id}/toggle-status");

    $response->assertStatus(200)
        ->assertJson(['data' => ['status' => 'active']]);
});

test('disabling a restaurant immediately blocks its owner from logging in', function () {
    ['user' => $owner, 'restaurant' => $restaurant] = createOwnerWithRestaurant('password123');

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $adminToken = $admin->createToken('token')->plainTextToken;

    $this->withHeaders(['Authorization' => "Bearer $adminToken"])
        ->patchJson("/api/restaurants/{$restaurant->id}/toggle-status")
        ->assertStatus(200);

    $response = $this->postJson('/api/owner/login', [
        'email' => $owner->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403);
});

test('disabling a restaurant immediately blocks an already-authenticated owner', function () {
    ['token' => $ownerToken, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $adminToken = $admin->createToken('token')->plainTextToken;

    $this->withHeaders(['Authorization' => "Bearer $adminToken"])
        ->patchJson("/api/restaurants/{$restaurant->id}/toggle-status")
        ->assertStatus(200);

    $response = $this->withHeaders(['Authorization' => "Bearer $ownerToken"])
        ->getJson('/api/owner/profile');

    $response->assertStatus(403);
});

test('disabling a restaurant hides it from the public menu', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $adminToken = $admin->createToken('token')->plainTextToken;

    $this->withHeaders(['Authorization' => "Bearer $adminToken"])
        ->patchJson("/api/restaurants/{$restaurant->id}/toggle-status")
        ->assertStatus(200);

    $response = $this->getJson("/api/public/r/{$restaurant->slug}/menu");

    $response->assertStatus(404);
});

test('non super admin cannot toggle restaurant status', function () {
    $user = User::factory()->create(['role' => UserRole::User->value]);
    $token = $user->createToken('token')->plainTextToken;

    $restaurant = Restaurant::factory()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/restaurants/{$restaurant->id}/toggle-status");

    $response->assertStatus(403);
});
