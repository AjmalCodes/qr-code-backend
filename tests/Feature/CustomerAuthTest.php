<?php

use App\Mail\CustomerEmailVerification;
use App\Models\Customer;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('signing up sends a verification email and does not verify the account', function () {
    Mail::fake();

    $response = $this->postJson('/api/customer/auth/signup', [
        'name' => 'Casey Customer',
        'email' => 'casey@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true, 'data' => ['email_verified' => false]]);

    $this->assertDatabaseHas('customers', ['email' => 'casey@example.com', 'email_verified_at' => null]);
    Mail::assertSent(CustomerEmailVerification::class, fn ($mail) => $mail->hasTo('casey@example.com'));
});

test('an unverified customer cannot log in', function () {
    $customer = Customer::factory()->unverified()->create(['password' => 'password123']);

    $response = $this->postJson('/api/customer/auth/login', [
        'email' => $customer->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403);
});

test('a verified customer can log in', function () {
    $customer = Customer::factory()->create(['password' => 'password123']);

    $response = $this->postJson('/api/customer/auth/login', [
        'email' => $customer->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)->assertJsonStructure(['data' => ['customer', 'token']]);
});

test('verifying with a valid token activates the account', function () {
    $customer = Customer::factory()->unverified()->create();
    $token = $customer->email_verification_token;

    $response = $this->postJson('/api/customer/auth/verify-email', ['token' => $token]);

    $response->assertStatus(200)->assertJson(['data' => ['email_verified' => true]]);
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'email_verification_token' => null]);
});

test('verifying with an unknown token fails', function () {
    $response = $this->postJson('/api/customer/auth/verify-email', ['token' => 'not-a-real-token']);

    $response->assertStatus(422);
});

test('verifying with an expired token fails', function () {
    $customer = Customer::factory()->unverified()->create([
        'email_verification_expires_at' => now()->subDay(),
    ]);

    $response = $this->postJson('/api/customer/auth/verify-email', ['token' => $customer->email_verification_token]);

    $response->assertStatus(422);
});

test('an authenticated customer can view their profile', function () {
    ['token' => $token, 'customer' => $customer] = createVerifiedCustomer();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/customer/auth/profile');

    $response->assertStatus(200)->assertJson(['data' => ['id' => $customer->id]]);
});

test('a staff token cannot access the customer profile endpoint', function () {
    ['token' => $token] = createOwnerWithRestaurant();

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/customer/auth/profile');

    $response->assertStatus(401);
});

test('forgot password sends a reset code and never reveals whether the email exists', function () {
    Notification::fake();
    $customer = Customer::factory()->create();

    $known = $this->postJson('/api/customer/auth/forgot-password', ['email' => $customer->email]);
    $unknown = $this->postJson('/api/customer/auth/forgot-password', ['email' => 'nobody@example.com']);

    $known->assertStatus(200);
    $unknown->assertStatus(200);
    expect($known->json('message'))->toBe($unknown->json('message'));

    Notification::assertSentTo($customer, CustomerResetPasswordNotification::class);
});

test('resetting the password with an invalid token fails', function () {
    $customer = Customer::factory()->create();

    $response = $this->postJson('/api/customer/auth/reset-password', [
        'email' => $customer->email,
        'token' => 'invalid-token',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertStatus(422);
});

test('registration requires a unique email and a confirmed password', function () {
    $response = $this->postJson('/api/customer/auth/signup', [
        'name' => 'No Password Confirmation',
        'email' => 'nopass@example.com',
        'password' => 'password123',
        'password_confirmation' => 'does-not-match',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['password']);
});
