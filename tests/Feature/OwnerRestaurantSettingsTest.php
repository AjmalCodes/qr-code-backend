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

test('owner can upload and replace a restaurant banner', function () {
    Storage::fake('public');

    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $firstBanner = UploadedFile::fake()->image('banner.jpg');

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/owner/restaurant-settings', [
            '_method' => 'PUT',
            'banner' => $firstBanner,
        ]);

    $response->assertStatus(200);
    $restaurant->refresh();
    expect($restaurant->banner)->not->toBeNull();
    Storage::disk('public')->assertExists($restaurant->banner);

    $originalBannerPath = $restaurant->banner;
    $secondBanner = UploadedFile::fake()->image('new-banner.png');

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/owner/restaurant-settings', [
            '_method' => 'PUT',
            'banner' => $secondBanner,
        ])->assertStatus(200);

    $restaurant->refresh();
    Storage::disk('public')->assertMissing($originalBannerPath);
    Storage::disk('public')->assertExists($restaurant->banner);
});

test('a logo and banner can be uploaded together without interfering with each other', function () {
    Storage::fake('public');

    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/owner/restaurant-settings', [
            '_method' => 'PUT',
            'logo' => UploadedFile::fake()->image('logo.jpg'),
            'banner' => UploadedFile::fake()->image('banner.jpg'),
        ])->assertStatus(200);

    $restaurant->refresh();
    expect($restaurant->logo)->not->toBeNull();
    expect($restaurant->banner)->not->toBeNull();
    Storage::disk('public')->assertExists($restaurant->logo);
    Storage::disk('public')->assertExists($restaurant->banner);
});

test('restaurant settings update rejects a non-image banner', function () {
    Storage::fake('public');

    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/owner/restaurant-settings', [
            '_method' => 'PUT',
            'banner' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['banner']);
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
