<?php

use App\Enums\UserRole;
use App\Mail\RestaurantCreated;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('super admin can create a restaurant', function () {
    Mail::fake();

    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/restaurants', [
        'name' => 'Pizza Palace',
        'email' => 'pizza@example.com',
        'phone' => '123-456-7890',
        'address' => '123 Main St',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'email', 'phone', 'address'],
        ])
        ->assertJson([
            'message' => 'Restaurant created successfully',
            'data' => [
                'name' => 'Pizza Palace',
                'email' => 'pizza@example.com',
                'phone' => '123-456-7890',
                'address' => '123 Main St',
            ],
        ]);

    $this->assertDatabaseHas('restaurants', [
        'name' => 'Pizza Palace',
        'email' => 'pizza@example.com',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'pizza@example.com',
        'role' => UserRole::Restaurant->value,
    ]);
});

test('super admin receives email when restaurant is created', function () {
    Mail::fake();

    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/restaurants', [
        'name' => 'Burger King',
        'email' => 'burgers@example.com',
        'phone' => '098-765-4321',
        'address' => '456 Oak Ave',
    ]);

    Mail::assertSent(RestaurantCreated::class, function ($mail) {
        return $mail->hasTo('burgers@example.com');
    });
});

test('super admin can view all restaurants', function () {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $restaurants = Restaurant::factory(3)->create();

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->getJson('/api/restaurants');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'phone', 'address'],
            ],
        ])
        ->assertJsonCount(3, 'data');
});

test('super admin can view a specific restaurant', function () {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $restaurant = Restaurant::factory()->create([
        'name' => 'Taco Fusion',
        'email' => 'tacos@example.com',
    ]);

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->getJson("/api/restaurants/{$restaurant->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $restaurant->id,
                'name' => 'Taco Fusion',
                'email' => 'tacos@example.com',
            ],
        ]);
});

test('super admin can update a restaurant', function () {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $restaurant = Restaurant::factory()->create([
        'name' => 'Old Name',
        'phone' => '111-111-1111',
    ]);

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->putJson("/api/restaurants/{$restaurant->id}", [
        'name' => 'New Restaurant Name',
        'phone' => '222-222-2222',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Restaurant updated successfully',
            'data' => [
                'id' => $restaurant->id,
                'name' => 'New Restaurant Name',
                'phone' => '222-222-2222',
            ],
        ]);

    $this->assertDatabaseHas('restaurants', [
        'id' => $restaurant->id,
        'name' => 'New Restaurant Name',
    ]);
});

test('super admin can delete a restaurant', function () {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $restaurant = Restaurant::factory()->create();
    $restaurantId = $restaurant->id;
    $userId = $restaurant->user_id;

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->deleteJson("/api/restaurants/{$restaurantId}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Restaurant deleted successfully']);

    $this->assertDatabaseMissing('restaurants', ['id' => $restaurantId]);
    $this->assertDatabaseMissing('users', ['id' => $userId]);
});

test('non-super admin cannot create a restaurant', function () {
    $regularUser = User::factory()->create([
        'role' => UserRole::User->value,
    ]);
    $token = $regularUser->createToken('api-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/restaurants', [
        'name' => 'Unauthorized Restaurant',
        'email' => 'unauthorized@example.com',
    ]);

    $response->assertStatus(403);
});

test('email is required when creating a restaurant', function () {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/restaurants', [
        'name' => 'Missing Email Restaurant',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('name is required when creating a restaurant', function () {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/restaurants', [
        'email' => 'noname@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('email must be unique when creating a restaurant', function () {
    Restaurant::factory()->create([
        'email' => 'unique@example.com',
    ]);

    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
    ]);
    $token = $superAdmin->createToken('api-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/restaurants', [
        'name' => 'Duplicate Email Restaurant',
        'email' => 'unique@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
