<?php

use App\Models\LoyaltyOffer;
use App\Models\Product;

test('owner can create a loyalty offer', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/loyalty-offers', [
            'title' => 'Buy 4 coffees get 1 free',
            'buy_quantity' => 4,
            'get_quantity' => 1,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => ['title' => 'Buy 4 coffees get 1 free', 'is_active' => true],
        ]);

    $this->assertDatabaseHas('loyalty_offers', [
        'restaurant_id' => $restaurant->id,
        'title' => 'Buy 4 coffees get 1 free',
    ]);
});

test('owner loyalty offer list is paginated with a per_page override', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    LoyaltyOffer::factory()->count(5)->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/loyalty-offers?per_page=2');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJson(['meta' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 2, 'total' => 5]]);
});

test('loyalty offer product must belong to the owners restaurant', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $foreignProduct = Product::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/loyalty-offers', [
            'title' => 'Sneaky offer',
            'product_id' => $foreignProduct->id,
            'buy_quantity' => 2,
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['product_id']);
});

test('buy_quantity is required', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/loyalty-offers', ['title' => 'Missing quantity']);

    $response->assertStatus(422)->assertJsonValidationErrors(['buy_quantity']);
});

test('owner can toggle a loyalty offers active state', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'is_active' => true]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/owner/loyalty-offers/{$offer->id}/toggle-active");

    $response->assertStatus(200)->assertJson(['data' => ['is_active' => false]]);
});

test('owner cannot view another restaurants loyalty offer', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/loyalty-offers/{$offer->id}");

    $response->assertStatus(404);
});

test('owner can delete a loyalty offer', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->deleteJson("/api/owner/loyalty-offers/{$offer->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('loyalty_offers', ['id' => $offer->id]);
});
