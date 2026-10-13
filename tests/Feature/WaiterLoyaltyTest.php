<?php

use App\Models\LoyaltyClaim;
use App\Models\LoyaltyOffer;

test('staff can verify a pending redemption code', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);

    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id]);
    $claim = LoyaltyClaim::factory()->create([
        'restaurant_id' => $restaurant->id,
        'loyalty_offer_id' => $offer->id,
        'redemption_code' => 'ABC12345',
        'status' => 'pending',
    ]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/waiter/loyalty/verify-redemption', ['code' => 'ABC12345']);

    $response->assertStatus(200)->assertJson(['data' => ['status' => 'verified']]);
    $this->assertDatabaseHas('loyalty_claims', ['id' => $claim->id, 'status' => 'verified']);
});

test('an already-verified code cannot be verified again', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);

    LoyaltyClaim::factory()->create([
        'restaurant_id' => $restaurant->id,
        'redemption_code' => 'USED1234',
        'status' => 'verified',
    ]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/waiter/loyalty/verify-redemption', ['code' => 'USED1234']);

    $response->assertStatus(422);
});

test('a code from another restaurant cannot be verified', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);

    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    LoyaltyClaim::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'redemption_code' => 'FOREIGN1',
        'status' => 'pending',
    ]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/waiter/loyalty/verify-redemption', ['code' => 'FOREIGN1']);

    $response->assertStatus(422);
});

test('an unknown code returns a validation error', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    ['token' => $token] = createWaiterForRestaurant($restaurant);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/waiter/loyalty/verify-redemption', ['code' => 'NOTREAL1']);

    $response->assertStatus(422);
});
