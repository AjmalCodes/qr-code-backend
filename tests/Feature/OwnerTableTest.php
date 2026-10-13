<?php

use App\Models\RestaurantTable;

test('owner can create a table and receives a QR payload', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/tables', ['label' => 'Table 5']);

    $response->assertStatus(201)
        ->assertJson(['success' => true, 'data' => ['label' => 'Table 5']])
        ->assertJsonStructure(['data' => ['id', 'label', 'qr_token', 'qr_target_url']]);

    $this->assertDatabaseHas('restaurant_tables', ['label' => 'Table 5']);
});

test('table labels must be unique per restaurant', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'Table 1']);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/tables', ['label' => 'Table 1']);

    $response->assertStatus(422)->assertJsonValidationErrors(['label']);
});

test('the same table label is allowed across different restaurants', function () {
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    RestaurantTable::factory()->create(['restaurant_id' => $otherRestaurant->id, 'label' => 'Table 1']);

    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/tables', ['label' => 'Table 1']);

    $response->assertStatus(201);
});

test('owner cannot view another restaurants table', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson("/api/owner/tables/{$table->id}");

    $response->assertStatus(404);
});

test('owner can regenerate a tables QR token, invalidating the old one', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    $originalToken = $table->qr_token;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->patchJson("/api/owner/tables/{$table->id}/regenerate-qr");

    $response->assertStatus(200);
    $newToken = $response->json('data.qr_token');

    expect($newToken)->not->toBe($originalToken);
    $this->assertDatabaseMissing('restaurant_tables', ['qr_token' => $originalToken]);
});

test('owner can delete a table', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->deleteJson("/api/owner/tables/{$table->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('restaurant_tables', ['id' => $table->id]);
});
