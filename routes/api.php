<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\AdminController;

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Public equipment routes
    Route::get('/equipment', [EquipmentController::class, 'index']);
    Route::get('/equipment/featured', [EquipmentController::class, 'featured']);
    Route::get('/equipment/popular', [EquipmentController::class, 'popular']);
    Route::get('/equipment/search', [EquipmentController::class, 'search']);
    Route::get('/equipment/{id}', [EquipmentController::class, 'show']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    // Banners
    Route::get('/banners', [BannerController::class, 'index']);

    // System settings (public ones)
    Route::get('/system/settings', [AdminController::class, 'publicSettings']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // User authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // User profile management
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::get('/user/listings', [UserController::class, 'listings']);
    Route::get('/user/favorites', [UserController::class, 'favorites']);
    Route::get('/user/subscription', [UserController::class, 'subscription']);

    // Equipment CRUD operations
    Route::post('/equipment', [EquipmentController::class, 'store']);
    Route::put('/equipment/{id}', [EquipmentController::class, 'update']);
    Route::delete('/equipment/{id}', [EquipmentController::class, 'destroy']);
    Route::post('/equipment/{id}/favorite', [EquipmentController::class, 'toggleFavorite']);
    Route::post('/equipment/{id}/sold', [EquipmentController::class, 'markSold']);
    Route::get('/equipment/{id}/analytics', [EquipmentController::class, 'analytics']);

    // Messaging
    Route::get('/conversations', [MessageController::class, 'conversations']);
    Route::get('/conversations/{id}', [MessageController::class, 'show']);
    Route::post('/conversations', [MessageController::class, 'store']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'sendMessage']);
    Route::put('/messages/{id}/read', [MessageController::class, 'markAsRead']);

    // Subscriptions
    Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    Route::get('/subscription/usage', [SubscriptionController::class, 'usage']);

    // Admin routes
    Route::middleware('role:admin,moderator')->prefix('admin')->group(function () {
        // Listing management
        Route::get('/listings', [AdminController::class, 'listings']);
        Route::post('/listings/{id}/approve', [AdminController::class, 'approveListing']);
        Route::post('/listings/{id}/reject', [AdminController::class, 'rejectListing']);
        Route::post('/listings/{id}/feature', [AdminController::class, 'featureListing']);

        // User management
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users/{id}/verify', [AdminController::class, 'verifyUser']);
        Route::post('/users/{id}/ban', [AdminController::class, 'banUser']);

        // Banner management
        Route::apiResource('banners', BannerController::class)->except(['index']);

        // System settings
        Route::get('/settings', [AdminController::class, 'settings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);

        // Analytics
        Route::get('/analytics/dashboard', [AdminController::class, 'dashboardAnalytics']);
        Route::get('/analytics/listings', [AdminController::class, 'listingAnalytics']);
        Route::get('/analytics/users', [AdminController::class, 'userAnalytics']);
    });
});
