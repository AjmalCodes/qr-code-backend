<?php

use App\Models\LoyaltyOffer;
use App\Models\Product;
use App\Models\RestaurantTable;

test('ordering a qualifying product builds loyalty progress for the customer', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $coffee = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active', 'name' => 'Coffee']);
    $offer = LoyaltyOffer::factory()->create([
        'restaurant_id' => $restaurant->id,
        'product_id' => $coffee->id,
        'buy_quantity' => 4,
        'is_active' => true,
    ]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/public/r/{$restaurant->slug}/orders", [
            'table_token' => $table->qr_token,
            'items' => [['product_id' => $coffee->id, 'quantity' => 1]],
        ])->assertStatus(201);

    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id,
        'loyalty_offer_id' => $offer->id,
        'progress_count' => 1,
    ]);
});

test('ordering multiple units in one order advances progress by that quantity', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $coffee = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'product_id' => $coffee->id, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/public/r/{$restaurant->slug}/orders", [
            'table_token' => $table->qr_token,
            'items' => [['product_id' => $coffee->id, 'quantity' => 3]],
        ])->assertStatus(201);

    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id,
        'loyalty_offer_id' => $offer->id,
        'progress_count' => 3,
    ]);
});

test('progress accumulates across separate visits until the offer is redeemable', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $coffee = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'product_id' => $coffee->id, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    // Four separate single-coffee orders, like four separate visits.
    collect(range(1, 4))->each(function () use ($token, $restaurant, $table, $coffee) {
        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson("/api/public/r/{$restaurant->slug}/orders", [
                'table_token' => $table->qr_token,
                'items' => [['product_id' => $coffee->id, 'quantity' => 1]],
            ])->assertStatus(201);
    });

    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id,
        'loyalty_offer_id' => $offer->id,
        'progress_count' => 4,
    ]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/customer/loyalty/active-offers?restaurant_slug={$restaurant->slug}");

    $response->assertStatus(200)->assertJson(['data' => [['is_redeemable' => true]]]);
});

test('ordering a different product does not advance a product-specific offer', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $coffee = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    $shawarma = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'product_id' => $coffee->id, 'buy_quantity' => 4]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/public/r/{$restaurant->slug}/orders", [
            'table_token' => $table->qr_token,
            'items' => [['product_id' => $shawarma->id, 'quantity' => 2]],
        ])->assertStatus(201);

    $this->assertDatabaseMissing('loyalty_progress', [
        'customer_id' => $customer->id,
        'loyalty_offer_id' => $offer->id,
    ]);
});

test('two independent offers for different products each track their own progress', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $coffee = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    $shawarma = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    $coffeeOffer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'product_id' => $coffee->id, 'buy_quantity' => 4]);
    $shawarmaOffer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'product_id' => $shawarma->id, 'buy_quantity' => 2]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/public/r/{$restaurant->slug}/orders", [
            'table_token' => $table->qr_token,
            'items' => [
                ['product_id' => $coffee->id, 'quantity' => 1],
                ['product_id' => $shawarma->id, 'quantity' => 2],
            ],
        ])->assertStatus(201);

    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id, 'loyalty_offer_id' => $coffeeOffer->id, 'progress_count' => 1,
    ]);
    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id, 'loyalty_offer_id' => $shawarmaOffer->id, 'progress_count' => 2,
    ]);
});

test('a restaurant-wide offer with no product_id counts purchases of any product', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    $offer = LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'product_id' => null, 'buy_quantity' => 5]);

    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/public/r/{$restaurant->slug}/orders", [
            'table_token' => $table->qr_token,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertStatus(201);

    $this->assertDatabaseHas('loyalty_progress', [
        'customer_id' => $customer->id, 'loyalty_offer_id' => $offer->id, 'progress_count' => 2,
    ]);
});

test('a guest order with no customer account earns no loyalty progress', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $coffee = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    LoyaltyOffer::factory()->create(['restaurant_id' => $restaurant->id, 'product_id' => $coffee->id, 'buy_quantity' => 4]);

    $this->postJson("/api/public/r/{$restaurant->slug}/orders", [
        'table_token' => $table->qr_token,
        'items' => [['product_id' => $coffee->id, 'quantity' => 1]],
    ])->assertStatus(201);

    $this->assertDatabaseCount('loyalty_progress', 0);
});

test('an inactive offer does not accumulate progress from new orders', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $coffee = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    LoyaltyOffer::factory()->inactive()->create(['restaurant_id' => $restaurant->id, 'product_id' => $coffee->id, 'buy_quantity' => 4]);

    ['token' => $token] = createVerifiedCustomer();

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson("/api/public/r/{$restaurant->slug}/orders", [
            'table_token' => $table->qr_token,
            'items' => [['product_id' => $coffee->id, 'quantity' => 1]],
        ])->assertStatus(201);

    $this->assertDatabaseCount('loyalty_progress', 0);
});
