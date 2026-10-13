<?php

use App\Enums\UserRole;
use App\Models\User;

test('owner can view their profile with restaurant details', function () {
    ['token' => $token, 'user' => $user, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/profile');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'restaurant' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                ],
            ],
        ]);
});

test('owner can update their profile', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/owner/profile', [
            'name' => 'Updated Owner Name',
            'phone' => '555-0100',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Updated Owner Name',
                'phone' => '555-0100',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Updated Owner Name',
        'phone' => '555-0100',
    ]);
});

test('owner profile update rejects a duplicate email', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['user' => $otherUser] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/owner/profile', [
            'email' => $otherUser->email,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('owner can change their password with the correct current password', function () {
    ['token' => $token, 'user' => $user] = createOwnerWithRestaurant('old-password123');

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/owner/change-password', [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Password changed successfully']);

    $loginResponse = $this->postJson('/api/owner/login', [
        'email' => $user->email,
        'password' => 'new-password456',
    ]);
    $loginResponse->assertStatus(200);
});

test('owner cannot change password with an incorrect current password', function () {
    ['token' => $token] = createOwnerWithRestaurant('old-password123');

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/owner/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});

test('owner cannot change password without confirmation matching', function () {
    ['token' => $token] = createOwnerWithRestaurant('old-password123');

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/owner/change-password', [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'does-not-match',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('super admin cannot access owner profile routes', function () {
    $admin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $admin->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/profile');

    $response->assertStatus(403);
});
