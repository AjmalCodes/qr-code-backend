<?php

use App\Models\Order;

test('owner report without a date range matches the dashboard shape', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'total_amount' => 25]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/reports');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'period', 'orders', 'revenue', 'menu', 'tables', 'qr_scans_total', 'subscription',
            ],
        ])
        ->assertJson(['data' => ['orders' => ['total' => 1], 'revenue' => ['total' => 25.0]]]);
});

test('owner report scoped to a date range only counts orders in that range', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    Order::factory()->create(['restaurant_id' => $restaurant->id, 'total_amount' => 100, 'created_at' => now()->subDays(20)]);
    Order::factory()->create(['restaurant_id' => $restaurant->id, 'total_amount' => 40, 'created_at' => now()]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/reports?from='.now()->subDay()->toDateString());

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'period' => ['from' => now()->subDay()->toDateString(), 'to' => null],
                'orders' => ['total' => 1],
                'revenue' => ['total' => 40.0],
            ],
        ]);
});

test('owner report rejects an invalid date', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/reports?from=not-a-date');

    $response->assertStatus(422);
});
