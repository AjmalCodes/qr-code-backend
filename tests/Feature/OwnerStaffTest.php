<?php

use App\Mail\StaffAccountCreated;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('owner can create a staff account and it receives credentials by email', function () {
    Mail::fake();

    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/staff', [
            'name' => 'Wendy Waiter',
            'email' => 'wendy@example.com',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => ['name' => 'Wendy Waiter', 'email' => 'wendy@example.com', 'role' => 'waiter'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'wendy@example.com',
        'role' => 'waiter',
        'restaurant_id' => $restaurant->id,
    ]);

    Mail::assertSent(StaffAccountCreated::class, fn ($mail) => $mail->hasTo('wendy@example.com'));
});

test('owner only sees their own restaurant staff', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();

    User::factory()->create(['role' => 'waiter', 'restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/staff');

    $response->assertStatus(200)->assertJsonCount(0, 'data');
});

test('owner cannot update another restaurants staff account', function () {
    ['token' => $token] = createOwnerWithRestaurant();
    ['restaurant' => $otherRestaurant] = createOwnerWithRestaurant();
    $staff = User::factory()->create(['role' => 'waiter', 'restaurant_id' => $otherRestaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson("/api/owner/staff/{$staff->id}", ['name' => 'Hijacked']);

    $response->assertStatus(404);
});

test('owner can delete a staff account', function () {
    ['token' => $token, 'restaurant' => $restaurant] = createOwnerWithRestaurant();
    $staff = User::factory()->create(['role' => 'waiter', 'restaurant_id' => $restaurant->id]);

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->deleteJson("/api/owner/staff/{$staff->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('users', ['id' => $staff->id]);
});

test('staff creation requires name and email', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/staff', []);

    $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email']);
});
