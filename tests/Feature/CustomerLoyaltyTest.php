<?php

use App\Models\Customer;
use App\Models\LoyaltyOffer;

test('active offers are listed for a restaurant by slug', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true, 'title' => 'Active Offer']);
    LoyaltyOffer::factory()->inactive()->create(['restaurant_id' => $restaurant->id, 'title' => 'Inactive Offer']);

    ['token' => $token] = createVerifiedCustomer();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/loyalty/active-offers?restaurant_slug={$restaurant->slug}");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Active Offer'])
        ->assertJsonMissing(['title' => 'Inactive Offer']);
});

test('a verified customer can claim an active offer', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'redemption_code', 'status', 'offer_title']]);

    $this->assertDatabaseHas('loyalty_claims', [
        'loyalty_offer_id' => $offer->id,
        'customer_id' => $customer->id,
        'status' => 'pending',
    ]);
});

test('an unverified customer cannot claim an offer', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id]);

    $customer = Customer::factory()->unverified()->create();
    $token = $customer->createToken('customer-api-token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id]);

    $response->assertStatus(403);
    $this->assertDatabaseMissing('loyalty_claims', ['loyalty_offer_id' => $offer->id]);
});

test('claiming an inactive offer is rejected', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->inactive()->create(['restaurant_id' => $restaurant->id]);

    ['token' => $token] = createVerifiedCustomer();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id]);

    $response->assertStatus(422);
});

test('claiming requires an unauthenticated guest to log in first', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id]);

    $response->assertStatus(401);
});

test('claiming a non-existent offer fails validation', function () {
    ['token' => $token] = createVerifiedCustomer();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => 999999]);

    $response->assertStatus(422)->assertJsonValidationErrors(['loyalty_offer_id']);
});
