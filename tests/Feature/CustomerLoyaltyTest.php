<?php

use App\Models\Customer;
use App\Models\LoyaltyOffer;
use App\Models\LoyaltyProgress;

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

test('active offers list is paginated with a per_page override', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    LoyaltyOffer::factory()->count(5)->create(['restaurant_id' => $restaurant->id, 'is_active' => true]);

    ['token' => $token] = createVerifiedCustomer();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/loyalty/active-offers?restaurant_slug={$restaurant->slug}&per_page=2");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJson(['meta' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 2, 'total' => 5]]);
});

test('active offers show the customers own progress and redeemability', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    LoyaltyProgress::factory()->create(['customer_id' => $customer->id, 'loyalty_offer_id' => $offer->id, 'progress_count' => 2]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/loyalty/active-offers?restaurant_slug={$restaurant->slug}");

    $response->assertStatus(200)
        ->assertJson(['data' => [['progress' => 2, 'buy_quantity' => 4, 'is_redeemable' => false]]]);
});

test('active offers mark an offer redeemable once progress meets buy_quantity', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    LoyaltyProgress::factory()->create(['customer_id' => $customer->id, 'loyalty_offer_id' => $offer->id, 'progress_count' => 4]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/loyalty/active-offers?restaurant_slug={$restaurant->slug}");

    $response->assertStatus(200)
        ->assertJson(['data' => [['progress' => 4, 'is_redeemable' => true]]]);
});

test('a customer with enough progress can claim the offer', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    LoyaltyProgress::factory()->create(['customer_id' => $customer->id, 'loyalty_offer_id' => $offer->id, 'progress_count' => 4]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'redemption_code', 'status', 'offer_title', 'get_quantity']]);

    $this->assertDatabaseHas('loyalty_claims', [
        'loyalty_offer_id' => $offer->id,
        'customer_id' => $customer->id,
        'status' => 'pending',
    ]);
});

test('claiming resets progress so the customer must buy again for the next reward', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    LoyaltyProgress::factory()->create(['customer_id' => $customer->id, 'loyalty_offer_id' => $offer->id, 'progress_count' => 4]);

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id])
        ->assertStatus(201);

    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id,
        'loyalty_offer_id' => $offer->id,
        'progress_count' => 0,
    ]);
});

test('claiming with surplus progress carries the remainder forward', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    LoyaltyProgress::factory()->create(['customer_id' => $customer->id, 'loyalty_offer_id' => $offer->id, 'progress_count' => 6]);

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id])
        ->assertStatus(201);

    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id,
        'loyalty_offer_id' => $offer->id,
        'progress_count' => 2,
    ]);
});

test('a customer without enough progress cannot claim the offer', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    LoyaltyProgress::factory()->create(['customer_id' => $customer->id, 'loyalty_offer_id' => $offer->id, 'progress_count' => 2]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id]);

    $response->assertStatus(422)->assertJsonValidationErrors(['offer']);
    $this->assertDatabaseMissing('loyalty_claims', ['loyalty_offer_id' => $offer->id, 'customer_id' => $customer->id]);
});

test('a customer with no purchase history at all cannot claim the offer', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offer->id]);

    $response->assertStatus(422);
    $this->assertDatabaseMissing('loyalty_claims', ['loyalty_offer_id' => $offer->id, 'customer_id' => $customer->id]);
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

test('one customer account tracks loyalty progress separately per restaurant', function () {
    ['restaurant' => $restaurantA] = createOwnerWithRestaurant();
    ['restaurant' => $restaurantB] = createOwnerWithRestaurant();
    $offerA = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurantA->id, 'is_active' => true, 'buy_quantity' => 4]);
    $offerB = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurantB->id, 'is_active' => true, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();
    LoyaltyProgress::factory()->create(['customer_id' => $customer->id, 'loyalty_offer_id' => $offerA->id, 'progress_count' => 4]);
    LoyaltyProgress::factory()->create(['customer_id' => $customer->id, 'loyalty_offer_id' => $offerB->id, 'progress_count' => 1]);

    // Fully earned at restaurant A — redeemable there.
    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/loyalty/active-offers?restaurant_slug={$restaurantA->slug}")
        ->assertJson(['data' => [['progress' => 4, 'is_redeemable' => true]]]);

    // Barely started at restaurant B — not redeemable there, independent of A.
    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/loyalty/active-offers?restaurant_slug={$restaurantB->slug}")
        ->assertJson(['data' => [['progress' => 1, 'is_redeemable' => false]]]);

    // The same account can claim at restaurant A without touching B's progress.
    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/customer/loyalty/claims', ['loyalty_offer_id' => $offerA->id])
        ->assertStatus(201);

    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id,
        'loyalty_offer_id' => $offerB->id,
        'progress_count' => 1,
    ]);
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
