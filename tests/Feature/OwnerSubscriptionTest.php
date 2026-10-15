<?php

use App\Models\Subscription;

test('owner can view their current subscription', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    Subscription::factory()->create(['restaurant_id' => $restaurant->id, 'plan_name' => 'Pro']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/subscription');

    $response->assertStatus(200)->assertJson(['data' => ['plan_name' => 'Pro']]);
});

test('owner sees the most recent subscription when there is history', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    Subscription::factory()->cancelled()->create(['restaurant_id' => $restaurant->id, 'plan_name' => 'Basic', 'created_at' => now()->subMonth()]);
    Subscription::factory()->create(['restaurant_id' => $restaurant->id, 'plan_name' => 'Enterprise', 'created_at' => now()]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/subscription');

    $response->assertStatus(200)->assertJson(['data' => ['plan_name' => 'Enterprise']]);
});

test('owner without a subscription gets a 404', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/subscription');

    $response->assertStatus(404);
});

test('owner cannot see another restaurants subscription', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    Subscription::factory()->create(['restaurant_id' => $otherRestaurant->id, 'plan_name' => 'Secret Plan']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/subscription');

    $response->assertStatus(404);
});
