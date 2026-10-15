<?php

use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;

test('super admin can list all subscriptions', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    Subscription::factory()->count(2)->create();
    Subscription::factory()->cancelled()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/subscriptions');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'restaurant', 'plan_name', 'amount', 'status', 'starts_at', 'ends_at']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

test('super admin can filter to only active subscriptions', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    Subscription::factory()->count(2)->create(['status' => 'active']);
    Subscription::factory()->cancelled()->create();
    Subscription::factory()->expired()->create();
    Subscription::factory()->trial()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/subscriptions?status=active');

    $response->assertStatus(200)->assertJsonCount(2, 'data');

    collect($response->json('data'))->each(
        fn ($subscription) => expect($subscription['status'])->toBe('active')
    );
});

test('super admin can search subscriptions by restaurant name', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $restaurant = Restaurant::factory()->create(['name' => 'Pizza Palace']);
    Subscription::factory()->create(['restaurant_id' => $restaurant->id]);
    Subscription::factory()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/subscriptions?search=Pizza');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Pizza Palace']);
});

test('super admin can create a subscription for a restaurant', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $restaurant = Restaurant::factory()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/subscriptions', [
            'restaurant_id' => $restaurant->id,
            'plan_name' => 'Pro',
            'amount' => 29.99,
            'ends_at' => now()->addYear()->toDateTimeString(),
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'plan_name' => 'Pro',
                'amount' => 29.99,
                'status' => 'active',
                'restaurant' => ['id' => $restaurant->id],
            ],
        ]);

    $this->assertDatabaseHas('subscriptions', ['restaurant_id' => $restaurant->id, 'plan_name' => 'Pro']);
});

test('subscription creation requires a restaurant, plan name, and amount', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/subscriptions', []);

    $response->assertStatus(422)->assertJsonValidationErrors(['restaurant_id', 'plan_name', 'amount']);
});

test('ends_at must be after starts_at', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $restaurant = Restaurant::factory()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/subscriptions', [
            'restaurant_id' => $restaurant->id,
            'plan_name' => 'Pro',
            'amount' => 29.99,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->subDay()->toDateTimeString(),
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['ends_at']);
});

test('super admin can update a subscription', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $subscription = Subscription::factory()->create(['plan_name' => 'Basic', 'amount' => 9.99]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson("/api/subscriptions/{$subscription->id}", ['plan_name' => 'Enterprise', 'amount' => 99.99]);

    $response->assertStatus(200)->assertJson(['data' => ['plan_name' => 'Enterprise', 'amount' => 99.99]]);
});

test('super admin can cancel a subscription', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $subscription = Subscription::factory()->create(['status' => 'active']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/subscriptions/{$subscription->id}/cancel");

    $response->assertStatus(200)->assertJson(['data' => ['status' => 'cancelled']]);
    $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'cancelled']);
    expect($subscription->fresh()->cancelled_at)->not->toBeNull();
});

test('super admin can delete a subscription record', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $subscription = Subscription::factory()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->deleteJson("/api/subscriptions/{$subscription->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
});

test('non super admin cannot access subscriptions', function () {
    $user = User::factory()->create(['role' => UserRole::User->value]);
    $token = $user->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/subscriptions');

    $response->assertStatus(403);
});

test('unauthenticated requests cannot access subscriptions', function () {
    $response = $this->getJson('/api/subscriptions');

    $response->assertStatus(401);
});
