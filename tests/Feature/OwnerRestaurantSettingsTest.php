<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('owner can update restaurant settings', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/owner/restaurant-settings', [
            'name' => 'The Grand Bistro',
            'description' => 'Fine dining experience',
            'address' => '456 Culinary Ave',
            'phone' => '555-0199',
            'currency' => 'EUR',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'The Grand Bistro',
                'description' => 'Fine dining experience',
                'currency' => 'EUR',
            ],
        ]);

    $this->assertDatabaseHas('restaurants', [
        'id' => $restaurant->id,
        'name' => 'The Grand Bistro',
        'currency' => 'EUR',
    ]);
});

test('owner can upload and replace a restaurant logo', function () {
    Storage::fake('public');

    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $firstLogo = UploadedFile::fake()->image('logo.jpg');

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/owner/restaurant-settings', [
            '_method' => 'PUT',
            'logo' => $firstLogo,
        ]);

    $response->assertStatus(200);
    $restaurant->refresh();
    expect($restaurant->logo)->not->toBeNull();
    Storage::disk('public')->assertExists($restaurant->logo);

    $originalLogoPath = $restaurant->logo;
    $secondLogo = UploadedFile::fake()->image('new-logo.png');

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/owner/restaurant-settings', [
            '_method' => 'PUT',
            'logo' => $secondLogo,
        ])->assertStatus(200);

    $restaurant->refresh();
    Storage::disk('public')->assertMissing($originalLogoPath);
    Storage::disk('public')->assertExists($restaurant->logo);
});

test('restaurant settings update rejects a non-image logo', function () {
    Storage::fake('public');

    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/owner/restaurant-settings', [
            '_method' => 'PUT',
            'logo' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['logo']);
});

test('restaurant settings email must be unique among restaurants', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/owner/restaurant-settings', [
            'email' => $otherRestaurant->email,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
