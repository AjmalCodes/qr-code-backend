<?php

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;

test('creating a subscription without ends_at defaults to a 30 day period', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;
    $restaurant = Restaurant::factory()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/subscriptions', [
            'restaurant_id' => $restaurant->id,
            'plan_name' => 'Pro',
            'amount' => 29.99,
        ]);

    $response->assertStatus(201);

    $subscription = Subscription::findOrFail($response->json('data.id'));
    expect((int) $subscription->starts_at->diffInDays($subscription->ends_at))->toBe(30);
});

test('an active subscription reports its remaining days', function () {
    $subscription = Subscription::factory()->create([
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addDays(10),
    ]);

    expect($subscription->remaining_days)->toBe(10);
});

test('a subscription past its end date reports zero remaining days', function () {
    $subscription = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => now()->subDay(),
    ]);

    expect($subscription->remaining_days)->toBe(0);
});

test('a subscription with no end date reports null remaining days', function () {
    $subscription = Subscription::factory()->create(['ends_at' => null]);

    expect($subscription->remaining_days)->toBeNull();
});

test('the expiry command flips overdue active subscriptions to expired', function () {
    $overdue = Subscription::factory()->create(['status' => 'active', 'ends_at' => now()->subDay()]);
    $stillActive = Subscription::factory()->create(['status' => 'active', 'ends_at' => now()->addDays(10)]);
    $alreadyCancelled = Subscription::factory()->cancelled()->create(['ends_at' => now()->subDay()]);

    $this->artisan('subscriptions:expire')->assertExitCode(0);

    expect($overdue->fresh()->status)->toBe(SubscriptionStatus::Expired);
    expect($stillActive->fresh()->status)->toBe(SubscriptionStatus::Active);
    expect($alreadyCancelled->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
});

test('owner subscription endpoint shows remaining days', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    Subscription::factory()->create(['restaurant_id' => $restaurant->id, 'ends_at' => now()->addDays(15)]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/subscription');

    $response->assertStatus(200)->assertJson(['data' => ['remaining_days' => 15]]);
});
