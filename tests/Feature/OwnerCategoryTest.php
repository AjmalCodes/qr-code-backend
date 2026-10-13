<?php

use App\Models\Category;

test('owner can list their categories in sorting order', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Desserts', 'sorting_order' => 2]);
    Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Starters', 'sorting_order' => 1]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/categories');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');

    $names = collect($response->json('data'))->pluck('name');
    expect($names->first())->toBe('Starters');
});

test('owner only sees their own restaurant categories', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();

    Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/categories');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

test('owner can create a category', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/categories', [
            'name' => 'Main Course',
            'description' => 'Hearty entrees',
            'sorting_order' => 1,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Main Course',
                'status' => 'active',
            ],
        ]);

    $this->assertDatabaseHas('categories', [
        'restaurant_id' => $restaurant->id,
        'name' => 'Main Course',
    ]);
});

test('category name is required', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/categories', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('owner can view a single category', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/categories/{$category->id}");

    $response->assertStatus(200)
        ->assertJson(['data' => ['id' => $category->id]]);
});

test('owner cannot view another restaurants category', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/categories/{$category->id}");

    $response->assertStatus(404);
});

test('owner can update their category', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Old Name']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson("/api/owner/categories/{$category->id}", ['name' => 'New Name']);

    $response->assertStatus(200)
        ->assertJson(['data' => ['name' => 'New Name']]);
});

test('owner cannot update another restaurants category', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson("/api/owner/categories/{$category->id}", ['name' => 'Hijacked']);

    $response->assertStatus(404);
    $this->assertDatabaseMissing('categories', ['id' => $category->id, 'name' => 'Hijacked']);
});

test('owner can delete their category', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->deleteJson("/api/owner/categories/{$category->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('owner cannot delete another restaurants category', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->deleteJson("/api/owner/categories/{$category->id}");

    $response->assertStatus(404);
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('owner can toggle a category status', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/owner/categories/{$category->id}/toggle-status");

    $response->assertStatus(200)
        ->assertJson(['data' => ['status' => 'inactive']]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/owner/categories/{$category->id}/toggle-status");

    $response->assertStatus(200)
        ->assertJson(['data' => ['status' => 'active']]);
});
