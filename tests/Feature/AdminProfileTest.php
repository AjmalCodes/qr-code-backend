<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('super admin can view their profile', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value, 'name' => 'Ada Admin']);
    $token = $admin->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/admin/profile');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => ['id' => $admin->id, 'name' => 'Ada Admin', 'role' => 'super_admin']]);
});

test('super admin can update their profile', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/admin/profile', [
            'name' => 'Updated Admin Name',
            'phone' => '555-0100',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => ['name' => 'Updated Admin Name', 'phone' => '555-0100']]);

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'Updated Admin Name', 'phone' => '555-0100']);
});

test('super admin can upload a profile photo', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/admin/profile', [
            '_method' => 'PUT',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    $response->assertStatus(200);
    $admin->refresh();
    expect($admin->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($admin->avatar);
    expect($response->json('data.avatar_url'))->not->toBeNull();
});

test('uploading a new profile photo replaces and deletes the old one', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/admin/profile', ['_method' => 'PUT', 'avatar' => UploadedFile::fake()->image('first.jpg')])
        ->assertStatus(200);

    $originalAvatar = $admin->refresh()->avatar;

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/admin/profile', ['_method' => 'PUT', 'avatar' => UploadedFile::fake()->image('second.jpg')])
        ->assertStatus(200);

    $admin->refresh();
    Storage::disk('public')->assertMissing($originalAvatar);
    Storage::disk('public')->assertExists($admin->avatar);
});

test('profile photo upload rejects a non-image file', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->post('/api/admin/profile', [
            '_method' => 'PUT',
            'avatar' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['avatar']);
});

test('admin profile update rejects a duplicate email', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
    $token = $admin->createToken('token')->plainTextToken;
    $other = User::factory()->create();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/admin/profile', ['email' => $other->email]);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('super admin can change their password with the correct current password', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value, 'password' => 'old-password123']);
    $token = $admin->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/admin/change-password', [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

    $response->assertStatus(200)->assertJson(['success' => true, 'message' => 'Password changed successfully']);

    $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'new-password456'])
        ->assertStatus(200);
});

test('super admin cannot change password with an incorrect current password', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value, 'password' => 'old-password123']);
    $token = $admin->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->putJson('/api/admin/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['current_password']);
});

test('non super admin cannot access admin profile routes', function () {
    $user = User::factory()->create(['role' => UserRole::User->value]);
    $token = $user->createToken('token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/admin/profile');

    $response->assertStatus(403);
});

test('restaurant owner cannot access admin profile routes', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/admin/profile');

    $response->assertStatus(403);
});

test('unauthenticated requests cannot access admin profile routes', function () {
    $response = $this->getJson('/api/admin/profile');

    $response->assertStatus(401);
});
