<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Simple test route for debugging
Route::get('/test', function () {
    return response()->json(['message' => 'API routes working', 'timestamp' => now()]);
});
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\CloudinaryController;

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

    // Sellers (public routes)
    Route::get('/sellers', [SellerController::class, 'index']);
    Route::get('/sellers/featured', [SellerController::class, 'featured']);
    Route::get('/sellers/{id}', [SellerController::class, 'show']);
    Route::get('/sellers/{id}/listings', [SellerController::class, 'listings']);
    Route::get('/sellers/{id}/stats', [SellerController::class, 'stats']);
    Route::get('/sellers/{id}/reviews', [SellerController::class, 'reviews']);
    Route::get('/seller/specialties', [SellerController::class, 'specialties']);

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
    Route::get('/user/stats', [UserController::class, 'stats']);
    Route::get('/user/activity', [UserController::class, 'activity']);
    Route::get('/user/dashboard', [UserController::class, 'dashboardOverview']);

    // Equipment CRUD operations
    Route::post('/equipment', [EquipmentController::class, 'store']);
    Route::put('/equipment/{id}', [EquipmentController::class, 'update']);
    Route::delete('/equipment/{id}', [EquipmentController::class, 'destroy']);
    Route::post('/equipment/{id}/images', [EquipmentController::class, 'uploadImages']);
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

    // Seller applications (protected routes)
    Route::post('/seller/apply', [SellerController::class, 'apply']);
    Route::get('/seller/application-status', [SellerController::class, 'applicationStatus']);
    Route::get('/seller/dashboard', [SellerController::class, 'dashboard']);

    // User invoice management
    Route::prefix('user')->group(function () {
        Route::get('/invoices', [AdminController::class, 'getUserInvoices']);
        Route::get('/invoices/{id}', [AdminController::class, 'getUserInvoice']);
        Route::post('/invoices/{id}/mark-paid', [AdminController::class, 'markInvoiceAsPaid']);
        Route::post('/invoices/{id}/payment-proof', [AdminController::class, 'submitPaymentProof']);
        Route::get('/invoices/{id}/download', [AdminController::class, 'downloadUserInvoice']);
    });

    // Cloudinary image management
    Route::prefix('cloudinary')->group(function () {
        Route::post('/signature', [CloudinaryController::class, 'getUploadSignature']);
        Route::post('/upload', [CloudinaryController::class, 'uploadImage']);
        Route::post('/upload-multiple', [CloudinaryController::class, 'uploadMultipleImages']);
        Route::delete('/image', [CloudinaryController::class, 'deleteImage']);
        Route::delete('/images', [CloudinaryController::class, 'deleteMultipleImages']);
        Route::get('/url', [CloudinaryController::class, 'getOptimizedUrl']);
        Route::get('/urls', [CloudinaryController::class, 'getMultipleUrls']);
    });

    // Admin routes
    Route::middleware('role:admin,moderator')->prefix('admin')->group(function () {
        // Listing management
        Route::get('/listings', [AdminController::class, 'listings']);
        Route::post('/listings/{id}/approve', [AdminController::class, 'approveListing']);
        Route::post('/listings/{id}/reject', [AdminController::class, 'rejectListing']);
        Route::post('/listings/{id}/feature', [AdminController::class, 'featureListing']);
        
        // Listing moderation
        Route::get('/listings/moderation', [AdminController::class, 'getListingsForModeration']);
        Route::post('/listings/{id}/moderate', [AdminController::class, 'moderateListing']);
        Route::post('/listings/{id}/extend', [AdminController::class, 'extendListingExpiration']);
        Route::post('/listings/cleanup', [AdminController::class, 'runAutoCleanup']);
        Route::get('/listings/moderation/stats', [AdminController::class, 'getModerationStats']);

        // User management
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::get('/users/search', [AdminController::class, 'searchUsers']);
        Route::get('/users/stats', [AdminController::class, 'getUserStats']);
        Route::get('/users/export', [AdminController::class, 'exportUsers']);
        Route::get('/users/{id}', [AdminController::class, 'getUser']);
        Route::get('/users/{id}/activity', [AdminController::class, 'getUserActivity']);
        Route::get('/users/{id}/login-history', [AdminController::class, 'getUserLoginHistory']);
        Route::get('/users/{id}/permissions', [AdminController::class, 'getUserPermissions']);
        Route::put('/users/{id}/permissions', [AdminController::class, 'updateUserPermissions']);
        Route::post('/users/{id}/verify', [AdminController::class, 'verifyUser']);
        Route::post('/users/{id}/verify-email', [AdminController::class, 'verifyUserEmail']);
        Route::post('/users/{id}/ban', [AdminController::class, 'banUser']);
        Route::post('/users/{id}/unban', [AdminController::class, 'unbanUser']);
        Route::post('/users/{id}/suspend', [AdminController::class, 'suspendUser']);
        Route::post('/users/{id}/unsuspend', [AdminController::class, 'unsuspendUser']);
        Route::post('/users/{id}/message', [AdminController::class, 'sendUserMessage']);
        Route::post('/users/{id}/reset-password', [AdminController::class, 'resetUserPassword']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::put('/users/{id}/status', [AdminController::class, 'updateUserStatus']);
        Route::put('/users/{id}/role', [AdminController::class, 'updateUserRole']);
        Route::post('/users/{id}/promote-to-seller', [AdminController::class, 'promoteToSeller']);
        
        // Email Verification
        Route::post('/email/verification', [AdminController::class, 'createEmailVerification']);
        Route::post('/email/verify-code', [AdminController::class, 'verifyEmailCode']);
        
        // User subscription management
        Route::post('/users/{id}/subscription', [AdminController::class, 'createUserSubscription']);
        Route::put('/users/{id}/subscription/upgrade', [AdminController::class, 'upgradeUserSubscription']);
        Route::post('/users/{id}/subscription/cancel', [AdminController::class, 'cancelUserSubscription']);

        // Banner management
        Route::apiResource('banners', BannerController::class);
        Route::get('/banners/active', [BannerController::class, 'active']);
        Route::get('/banners/revenue-analytics', [AdminController::class, 'getBannerRevenueAnalytics']);
        Route::get('/banners/pricing-tiers', [AdminController::class, 'getBannerPricingTiers']);

        // System settings
        Route::get('/settings', [AdminController::class, 'settings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);
        Route::get('/system/settings', [AdminController::class, 'getAllSystemSettings']);
        Route::put('/system/setting', [AdminController::class, 'updateSystemSetting']);
        Route::delete('/system/setting/{key}', [AdminController::class, 'deleteSystemSetting']);

        // Analytics
        Route::get('/analytics/dashboard', [AdminController::class, 'dashboardAnalytics']);
        Route::get('/analytics/listings', [AdminController::class, 'listingAnalytics']);
        Route::get('/analytics/users', [AdminController::class, 'userAnalytics']);
        Route::get('/dashboard/analytics', [AdminController::class, 'dashboardAnalytics']);
        
        // System Metrics
        Route::get('/system/metrics', [AdminController::class, 'getSystemMetrics']);

        // Seller Application Management
        Route::get('/seller-applications', [AdminController::class, 'getSellerApplications']);
        Route::get('/seller-applications/stats', [AdminController::class, 'getSellerApplicationStats']);
        Route::get('/seller-applications/{id}', [AdminController::class, 'getSellerApplication']);
        Route::post('/seller-applications/approve', [AdminController::class, 'approveSellerApplication']);
        Route::post('/seller-applications/{id}/reject', [AdminController::class, 'rejectSellerApplication']);
        Route::patch('/seller-applications/{id}/status', [AdminController::class, 'updateApplicationStatus']);

        // Invoice Management
        Route::get('/invoices', [AdminController::class, 'getInvoices']);
        Route::get('/invoices/stats', [AdminController::class, 'getInvoiceStats']);
        Route::get('/invoices/{id}', [AdminController::class, 'getInvoice']);
        Route::post('/invoices', [AdminController::class, 'createInvoice']);
        Route::post('/invoices/generate', [AdminController::class, 'generateSellerInvoice']);
        Route::post('/invoices/{id}/send', [AdminController::class, 'sendSellerInvoice']);
        Route::get('/invoices/{id}/download', [AdminController::class, 'downloadInvoice']);
        Route::get('/documents/download', [AdminController::class, 'downloadSellerDocument']);

        // Subscription Plans Management
        Route::get('/subscription-plans', [AdminController::class, 'getSubscriptionPlans']);
        Route::get('/subscription-plans/{id}/stats', [AdminController::class, 'getSubscriptionPlanStats']);
        Route::post('/subscription-plans', [AdminController::class, 'createSubscriptionPlan']);
        Route::put('/subscription-plans/{id}', [AdminController::class, 'updateSubscriptionPlan']);
        Route::delete('/subscription-plans/{id}', [AdminController::class, 'deleteSubscriptionPlan']);
    });
});
