<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('owner can list their products', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    Product::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/products');

    $response->assertStatus(200)->assertJsonCount(3, 'data');
});

test('owner only sees their own restaurant products', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    Product::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/products');

    $response->assertStatus(200)->assertJsonCount(0, 'data');
});

test('owner can filter products by category and status', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);

    $matching = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'status' => 'active',
    ]);
    Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'status' => 'out_of_stock',
    ]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/products?category_id={$category->id}&status=active");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson(['data' => [['id' => $matching->id]]]);
});

test('owner can create a product', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/products', [
            'category_id' => $category->id,
            'name' => 'Cheeseburger',
            'description' => 'Juicy beef patty',
            'price' => 12.50,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Cheeseburger',
                'price' => 12.5,
                'status' => 'active',
            ],
        ]);

    $this->assertDatabaseHas('products', [
        'restaurant_id' => $restaurant->id,
        'name' => 'Cheeseburger',
    ]);
});

test('owner can create a product with an image', function () {
    Storage::fake('public');
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/owner/products', [
            'name' => 'Margherita Pizza',
            'price' => 9.99,
            'image' => UploadedFile::fake()->image('pizza.jpg'),
        ]);

    $response->assertStatus(201);
    $product = Product::where('restaurant_id', $restaurant->id)->first();
    expect($product->image)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image);
});

test('a product cannot be assigned to another restaurants category', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $foreignCategory = Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/products', [
            'category_id' => $foreignCategory->id,
            'name' => 'Sneaky Item',
            'price' => 5,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category_id']);
});

test('product name and price are required', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/products', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'price']);
});

test('owner can view a product with its category', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Drinks']);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJson(['data' => ['id' => $product->id, 'category' => ['name' => 'Drinks']]]);
});

test('owner cannot view another restaurants product', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $product = Product::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/products/{$product->id}");

    $response->assertStatus(404);
});

test('owner can update a product', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'price' => 10]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson("/api/owner/products/{$product->id}", ['price' => 15.75]);

    $response->assertStatus(200)
        ->assertJson(['data' => ['price' => 15.75]]);
});

test('owner cannot update another restaurants product', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $product = Product::factory()->create(['restaurant_id' => $otherRestaurant->id, 'price' => 10]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson("/api/owner/products/{$product->id}", ['price' => 999]);

    $response->assertStatus(404);
    $this->assertDatabaseMissing('products', ['id' => $product->id, 'price' => 999]);
});

test('owner can delete a product', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->deleteJson("/api/owner/products/{$product->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('owner can update a product status', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id, 'status' => 'active']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/owner/products/{$product->id}/status", ['status' => 'out_of_stock']);

    $response->assertStatus(200)
        ->assertJson(['data' => ['status' => 'out_of_stock']]);
});

test('product status must be a valid enum value', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/owner/products/{$product->id}/status", ['status' => 'discontinued']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});
