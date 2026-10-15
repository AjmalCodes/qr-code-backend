<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;

test('super admin can view platform dashboard stats', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    Restaurant::factory()->create(['status' => 'active']);
    Restaurant::factory()->create(['status' => 'inactive']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/dashboard/stats');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'total_restaurants',
                'active_restaurants',
                'inactive_restaurants',
                'total_orders',
                'orders_today',
                'pending_orders',
                'active_subscriptions',
                'recent_restaurants' => [
                    '*' => ['id', 'name', 'slug', 'status', 'created_at'],
                ],
            ],
        ])
        ->assertJson([
            'data' => [
                'total_restaurants' => 2,
                'active_restaurants' => 1,
                'inactive_restaurants' => 1,
            ],
        ]);
});

test('dashboard stats count only active subscriptions', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    Subscription::factory()->create(['status' => 'active']);
    Subscription::factory()->create(['status' => 'active']);
    Subscription::factory()->cancelled()->create();
    Subscription::factory()->expired()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/dashboard/stats');

    $response->assertStatus(200)->assertJson(['data' => ['active_subscriptions' => 2]]);
});

test('dashboard stats list the most recently created restaurants', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    Restaurant::factory()->create(['name' => 'Older Place', 'created_at' => now()->subDays(2)]);
    $newest = Restaurant::factory()->create(['name' => 'Newest Place', 'created_at' => now()]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/dashboard/stats');

    $response->assertStatus(200);
    expect($response->json('data.recent_restaurants.0.name'))->toBe($newest->name);
});

test('non super admin cannot view dashboard stats', function () {
    $user = User::factory()->create(['role' => UserRole::User->value]);
    $token = $user->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/dashboard/stats');

    $response->assertStatus(403);
});

test('super admin can search restaurants by name', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    Restaurant::factory()->create(['name' => 'Pizza Palace']);
    Restaurant::factory()->create(['name' => 'Burger Barn']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurants?search=Pizza');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [['name' => 'Pizza Palace']]]);
});

test('super admin can filter restaurants by status', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    Restaurant::factory()->create(['status' => 'active']);
    Restaurant::factory()->create(['status' => 'inactive']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurants?status=inactive');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [['status' => 'inactive']]]);
});

test('restaurant list is paginated', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    Restaurant::factory()->count(3)->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/restaurants?per_page=2');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJson(['meta' => ['total' => 3, 'per_page' => 2]]);
});
