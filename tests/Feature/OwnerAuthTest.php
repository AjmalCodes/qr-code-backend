<?php

use App\Enums\UserRole;
use App\Models\User;

test('restaurant owner can login with valid credentials', function () {
    ['user' => $user] = createOwnerWithRestaurant('password123');

    $response = $this->postJson('/api/owner/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Login successful',
        ])
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'role', 'restaurant'],
                'token',
            ],
        ]);
});

test('owner login fails with invalid credentials', function () {
    ['user' => $user] = createOwnerWithRestaurant('password123');

    $response = $this->postJson('/api/owner/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('non restaurant owner accounts cannot login through the owner endpoint', function () {
    $user = User::factory()->create([
        'role' => UserRole::User->value,
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/owner/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
        ->assertJson(['success' => false]);
});

test('super admin cannot login through the owner endpoint', function () {
    $admin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/owner/login', [
        'email' => $admin->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403);
});

test('owner can logout', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/logout');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Logged out successfully']);
});

test('unauthenticated requests to protected owner routes are rejected', function () {
    $response = $this->getJson('/api/owner/profile');

    $response->assertStatus(401);
});

test('a restaurant owner without a restaurant record is denied access', function () {
    $user = User::factory()->create([
        'role' => UserRole::Restaurant->value,
        'password' => 'password123',
    ]);
    $token = $user->createToken('owner-api-token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/profile');

    $response->assertStatus(404);
});
