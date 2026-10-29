<?php

use App\Enums\UserRole;
use App\Mail\NewRestaurantRegistered;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('a restaurant owner can self-register and starts out pending', function () {
    Mail::fake();

    $response = $this->postJson('/api/owner/register', [
        'name' => 'Sunset Diner',
        'email' => 'owner@sunsetdiner.com',
        'phone' => '555-0123',
        'address' => '12 Ocean Drive',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => ['name' => 'Sunset Diner', 'email' => 'owner@sunsetdiner.com', 'status' => 'pending'],
        ]);

    $this->assertDatabaseHas('restaurants', [
        'name' => 'Sunset Diner',
        'email' => 'owner@sunsetdiner.com',
        'status' => 'pending',
    ]);
    $this->assertDatabaseHas('users', [
        'email' => 'owner@sunsetdiner.com',
        'role' => UserRole::Restaurant->value,
    ]);
});

test('registering sends a notification to the platform administrator, not the owner', function () {
    Mail::fake();
    config(['app.admin_notification_email' => 'ajmalmoon86@gmail.com']);

    $this->postJson('/api/owner/register', [
        'name' => 'Sunset Diner',
        'email' => 'owner@sunsetdiner.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    Mail::assertSent(NewRestaurantRegistered::class, function ($mail) {
        return $mail->hasTo('ajmalmoon86@gmail.com');
    });
    Mail::assertNotSent(NewRestaurantRegistered::class, fn ($mail) => $mail->hasTo('owner@sunsetdiner.com'));
});

test('a pending restaurant owner cannot log in yet', function () {
    ['user' => $user] = createOwnerWithRestaurant('password123', [], ['status' => 'pending']);

    $response = $this->postJson('/api/owner/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
        ->assertJson(['success' => false, 'message' => 'Your restaurant registration is still pending review by our team.']);
});

test('registration requires name, email, and password', function () {
    $response = $this->postJson('/api/owner/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration email must be unique among restaurants', function () {
    $existing = Restaurant::factory()->create();

    $response = $this->postJson('/api/owner/register', [
        'name' => 'Copycat Cafe',
        'email' => $existing->email,
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('restaurant owner can login with valid credentials', function () {
    ['user' => $user] = createOwnerWithRestaurant('password123');

    $response = $this->postJson('/api/owner/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Login successful',
        ])
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'role', 'restaurant'],
                'token',
            ],
        ]);
});

test('owner login fails with invalid credentials', function () {
    ['user' => $user] = createOwnerWithRestaurant('password123');

    $response = $this->postJson('/api/owner/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('non restaurant owner accounts cannot login through the owner endpoint', function () {
    $user = User::factory()->create([
        'role' => UserRole::User->value,
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/owner/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
        ->assertJson(['success' => false]);
});

test('super admin cannot login through the owner endpoint', function () {
    $admin = User::factory()->create([
        'role' => UserRole::SuperAdmin->value,
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/owner/login', [
        'email' => $admin->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403);
});

test('owner can logout', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/owner/logout');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Logged out successfully']);
});

test('unauthenticated requests to protected owner routes are rejected', function () {
    $response = $this->getJson('/api/owner/profile');

    $response->assertStatus(401);
});

test('a restaurant owner without a restaurant record is denied access', function () {
    $user = User::factory()->create([
        'role' => UserRole::Restaurant->value,
        'password' => 'password123',
    ]);
    $token = $user->createToken('owner-api-token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/owner/profile');

    $response->assertStatus(404);
});
