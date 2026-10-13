<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a restaurant owner (with a known password) and their restaurant,
 * returning both plus a ready-to-use Sanctum bearer token.
 *
 * @return array{user: User, restaurant: Restaurant, token: string}
 */
function createOwnerWithRestaurant(string $password = 'password123', array $userAttributes = [], array $restaurantAttributes = []): array
{
    $user = User::factory()->create(array_merge([
        'role' => UserRole::Restaurant->value,
        'password' => $password,
    ], $userAttributes));

    $restaurant = Restaurant::factory()->create(array_merge([
        'user_id' => $user->id,
    ], $restaurantAttributes));

    $token = $user->createToken('owner-api-token')->plainTextToken;

    return ['user' => $user, 'restaurant' => $restaurant, 'token' => $token];
}

/**
 * Create a waiter (with a known password) on an existing restaurant,
 * returning the staff user plus a ready-to-use Sanctum bearer token.
 *
 * @return array{user: User, token: string}
 */
function createWaiterForRestaurant(Restaurant $restaurant, string $password = 'password123', array $userAttributes = []): array
{
    $user = User::factory()->create(array_merge([
        'role' => UserRole::Waiter->value,
        'password' => $password,
        'restaurant_id' => $restaurant->id,
    ], $userAttributes));

    $token = $user->createToken('waiter-api-token')->plainTextToken;

    return ['user' => $user, 'token' => $token];
}

/**
 * Create a verified customer (with a known password), returning the
 * customer plus a ready-to-use Sanctum bearer token.
 *
 * @return array{customer: Customer, token: string}
 */
function createVerifiedCustomer(string $password = 'password123', array $attributes = []): array
{
    $customer = Customer::factory()->create(array_merge([
        'password' => $password,
    ], $attributes));

    $token = $customer->createToken('customer-api-token')->plainTextToken;

    return ['customer' => $customer, 'token' => $token];
}
