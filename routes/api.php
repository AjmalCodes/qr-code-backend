<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Customer\LoyaltyController as CustomerLoyaltyController;
use App\Http\Controllers\Api\Owner\AuthController as OwnerAuthController;
use App\Http\Controllers\Api\Owner\CategoryController;
use App\Http\Controllers\Api\Owner\LoyaltyOfferController;
use App\Http\Controllers\Api\Owner\ProductController;
use App\Http\Controllers\Api\Owner\ProfileController;
use App\Http\Controllers\Api\Owner\RestaurantSettingsController;
use App\Http\Controllers\Api\Owner\StaffController;
use App\Http\Controllers\Api\Owner\TableController;
use App\Http\Controllers\Api\Public\MenuController as PublicMenuController;
use App\Http\Controllers\Api\Public\OrderController as PublicOrderController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\Waiter\AuthController as WaiterAuthController;
use App\Http\Controllers\Api\Waiter\LoyaltyController as WaiterLoyaltyController;
use App\Http\Controllers\Api\Waiter\OrderController as WaiterOrderController;
use App\Http\Middleware\EnsureCustomer;
use App\Http\Middleware\EnsureRestaurantOwner;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureVerifiedCustomer;
use App\Http\Middleware\EnsureWaiterStaff;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Module 1: Super Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', EnsureSuperAdmin::class])->group(function () {
    Route::apiResource('restaurants', RestaurantController::class);
    Route::patch('/restaurants/{restaurant}/toggle-status', [RestaurantController::class, 'toggleStatus']);
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
});

/*
|--------------------------------------------------------------------------
| Module 2: Restaurant Owner Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('owner')->group(function () {
    Route::post('/login', [OwnerAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', EnsureRestaurantOwner::class])->group(function () {
        Route::post('/logout', [OwnerAuthController::class, 'logout']);

        // Account settings
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/change-password', [ProfileController::class, 'changePassword']);
        Route::put('/restaurant-settings', [RestaurantSettingsController::class, 'update']);

        // Category management
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        Route::patch('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);

        // Product / menu item management
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::patch('/products/{id}/status', [ProductController::class, 'updateStatus']);

        // Table / QR management
        Route::get('/tables', [TableController::class, 'index']);
        Route::post('/tables', [TableController::class, 'store']);
        Route::get('/tables/{id}', [TableController::class, 'show']);
        Route::put('/tables/{id}', [TableController::class, 'update']);
        Route::delete('/tables/{id}', [TableController::class, 'destroy']);
        Route::patch('/tables/{id}/regenerate-qr', [TableController::class, 'regenerateQrCode']);

        // Staff accounts
        Route::get('/staff', [StaffController::class, 'index']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::get('/staff/{id}', [StaffController::class, 'show']);
        Route::put('/staff/{id}', [StaffController::class, 'update']);
        Route::delete('/staff/{id}', [StaffController::class, 'destroy']);

        // Loyalty offers
        Route::get('/loyalty-offers', [LoyaltyOfferController::class, 'index']);
        Route::post('/loyalty-offers', [LoyaltyOfferController::class, 'store']);
        Route::get('/loyalty-offers/{id}', [LoyaltyOfferController::class, 'show']);
        Route::put('/loyalty-offers/{id}', [LoyaltyOfferController::class, 'update']);
        Route::delete('/loyalty-offers/{id}', [LoyaltyOfferController::class, 'destroy']);
        Route::patch('/loyalty-offers/{id}/toggle-active', [LoyaltyOfferController::class, 'toggleActive']);
    });
});

/*
|--------------------------------------------------------------------------
| Module 3: Public Customer QR Menu & Ordering (no account required)
|--------------------------------------------------------------------------
*/
Route::prefix('public/r/{slug}')->group(function () {
    Route::get('/menu', [PublicMenuController::class, 'show']);
    Route::post('/orders', [PublicOrderController::class, 'store']);
    Route::get('/orders/{orderNumber}/status', [PublicOrderController::class, 'status']);
});

/*
|--------------------------------------------------------------------------
| Module 4: Waiter / Kitchen Real-Time Panel
|--------------------------------------------------------------------------
*/
Route::prefix('waiter')->group(function () {
    Route::post('/login', [WaiterAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', EnsureWaiterStaff::class])->group(function () {
        Route::post('/logout', [WaiterAuthController::class, 'logout']);
        Route::get('/orders', [WaiterOrderController::class, 'index']);
        Route::get('/orders/stream', [WaiterOrderController::class, 'stream']);
        Route::patch('/orders/{id}/status', [WaiterOrderController::class, 'updateStatus']);
        Route::post('/loyalty/verify-redemption', [WaiterLoyaltyController::class, 'verifyRedemption']);
    });
});

/*
|--------------------------------------------------------------------------
| Module 5: Customer Account & Verified Loyalty
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->group(function () {
    Route::post('/auth/signup', [CustomerAuthController::class, 'signup']);
    Route::post('/auth/verify-email', [CustomerAuthController::class, 'verifyEmail']);
    Route::post('/auth/login', [CustomerAuthController::class, 'login']);
    Route::post('/auth/forgot-password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [CustomerAuthController::class, 'resetPassword']);

    Route::middleware(['auth:sanctum', EnsureCustomer::class])->group(function () {
        Route::post('/auth/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/auth/profile', [CustomerAuthController::class, 'profile']);

        Route::get('/loyalty/active-offers', [CustomerLoyaltyController::class, 'activeOffers']);

        Route::middleware(EnsureVerifiedCustomer::class)->group(function () {
            Route::post('/loyalty/claims', [CustomerLoyaltyController::class, 'claim']);
        });
    });
});
