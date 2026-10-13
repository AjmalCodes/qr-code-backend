<?php

use App\Enums\UserRole;
use App\Models\User;

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::User->value,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email', 'role'],
            'token',
        ])
        ->assertJson([
            'message' => 'Login successful',
            'user' => [
                'email' => 'test@example.com',
                'role' => UserRole::User->value,
            ],
        ]);
});

test('user cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('super admin can login', function () {
    $user = User::factory()->create([
        'email' => 'superadmin@gmail.com',
        'password' => bcrypt('12345678'),
        'role' => UserRole::SuperAdmin->value,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'superadmin@gmail.com',
        'password' => '12345678',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'user' => [
                'role' => UserRole::SuperAdmin->value,
            ],
        ]);
});

test('user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);
});

test('authenticated user can view their profile', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
        ])
        ->assertJson([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
});

test('unauthenticated user cannot access protected routes', function () {
    $response = $this->getJson('/api/me');

    $response->assertStatus(401);
});
