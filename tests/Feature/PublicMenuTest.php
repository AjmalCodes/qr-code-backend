<?php

use App\Models\Category;
use App\Models\Product;

test('public menu lists active categories with active products', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();

    $visibleCategory = Category::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    Product::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $visibleCategory->id, 'status' => 'active', 'name' => 'Visible Burger']);

    $response = $this->getJson("/api/public/r/{$restaurant->slug}/menu");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['restaurant', 'categories']])
        ->assertJsonFragment(['name' => 'Visible Burger']);
});

test('public menu hides inactive categories', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $hiddenCategory = Category::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'inactive', 'name' => 'Hidden Category']);
    Product::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $hiddenCategory->id, 'status' => 'active']);

    $response = $this->getJson("/api/public/r/{$restaurant->slug}/menu");

    $response->assertStatus(200)->assertJsonMissing(['name' => 'Hidden Category']);
});

test('public menu hides out-of-stock and inactive products', function () {
    ['restaurant' => $restaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);
    Product::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'status' => 'out_of_stock', 'name' => 'Sold Out Item']);
    Product::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'status' => 'inactive', 'name' => 'Retired Item']);

    $response = $this->getJson("/api/public/r/{$restaurant->slug}/menu");

    $response->assertStatus(200)
        ->assertJsonMissing(['name' => 'Sold Out Item'])
        ->assertJsonMissing(['name' => 'Retired Item']);
});

test('unknown restaurant slug returns 404', function () {
    $response = $this->getJson('/api/public/r/does-not-exist/menu');

    $response->assertStatus(404);
});
