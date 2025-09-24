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
use App\Http\Controllers\Api\BannerPurchaseController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\CloudinaryController;
use App\Http\Controllers\Api\Communication\EmailConfigController;
use App\Http\Controllers\Api\Communication\NewsLetterTemplateController;
use App\Http\Controllers\Api\Communication\NewsLetterController;
use App\Http\Controllers\Api\Communication\NewsletterSettingsController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\EmailConfigurationController;
use App\Http\Controllers\Api\FinancialTransactionController;
use App\Http\Controllers\Api\SystemMonitorController;
use App\Http\Controllers\Api\Communication\NewsletterSubscriberController;
use App\Models\EmailConfig;

// Public routes
Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'API is healthy',
            'timestamp' => now(),
            'version' => '1.0.0'
        ]);
    });

    // Authentication routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
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
    Route::get('/banners/homepage', [BannerController::class, 'getHomepageBanners']);
    Route::get('/banners/category', [BannerController::class, 'getCategoryBanners']);
    Route::get('/banners/listing-detail', [BannerController::class, 'getListingDetailBanners']);
    Route::get('/banners/configuration', [BannerController::class, 'getConfiguration']);
    Route::post('/banners/{id}/click', [BannerController::class, 'trackClick']);
    Route::post('/banners/{id}/impression', [BannerController::class, 'trackImpression']);

    // Banner Purchase (public pricing)
    Route::get('/banner-purchase/pricing', [BannerPurchaseController::class, 'getPricing']);

    // Inquiries (public routes)
    Route::post('/inquiries', [InquiryController::class, 'store']);
    Route::get('/inquiries', [InquiryController::class, 'index']);

    // Payment webhooks (public)
    Route::post('/webhooks/flutterwave', [PaymentController::class, 'flutterwaveWebhook']);
    Route::post('/webhooks/paystack', [PaymentController::class, 'paystackWebhook']);

    // Sellers (public routes)
    Route::get('/sellers', [SellerController::class, 'index']);
    Route::get('/sellers/featured', [SellerController::class, 'featured']);
    Route::get('/sellers/{id}', [SellerController::class, 'show']);
    Route::get('/sellers/{id}/listings', [SellerController::class, 'listings']);
    Route::get('/sellers/{id}/stats', [SellerController::class, 'stats']);
    Route::get('/sellers/{id}/reviews', [SellerController::class, 'reviews']);
    Route::get('/seller/specialties', [SellerController::class, 'specialties']);

    // Knowledge Base (public routes)
    Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index']);
    Route::get('/knowledge-base/categories', [KnowledgeBaseController::class, 'categories']);
    Route::get('/knowledge-base/featured', [KnowledgeBaseController::class, 'featured']);
    Route::get('/knowledge-base/popular', [KnowledgeBaseController::class, 'popular']);
    Route::get('/knowledge-base/search', [KnowledgeBaseController::class, 'search']);
    Route::get('/knowledge-base/slug/{slug}', [KnowledgeBaseController::class, 'getBySlug']);
    Route::get('/knowledge-base/{id}', [KnowledgeBaseController::class, 'show']);
    Route::get('/knowledge-base/{id}/related', [KnowledgeBaseController::class, 'getRelated']);
    Route::post('/knowledge-base/{id}/view', [KnowledgeBaseController::class, 'trackView']);

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
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::post('/conversations', [ConversationController::class, 'createOrGet']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'sendMessage']);
    Route::put('/conversations/{id}/read', [ConversationController::class, 'markAsRead']);
    Route::put('/conversations/{id}/archive', [ConversationController::class, 'archive']);
    Route::get('/messages/unread-count', [ConversationController::class, 'getUnreadCount']);

    // System Conversations (User to Admin)
    Route::get('/system-conversations', [\App\Http\Controllers\Api\AdminMessagingController::class, 'getSystemConversations']);
    Route::get('/system-conversations/{id}', [\App\Http\Controllers\Api\AdminMessagingController::class, 'getSystemConversation']);
    Route::post('/system-conversations', [\App\Http\Controllers\Api\AdminMessagingController::class, 'startSystemConversation']);
    Route::post('/system-conversations/{id}/messages', [\App\Http\Controllers\Api\AdminMessagingController::class, 'sendSystemMessage']);
    Route::patch('/system-conversations/{id}/read', [\App\Http\Controllers\Api\AdminMessagingController::class, 'markConversationAsRead']);

    // Notifications
    Route::get('/notifications/summary', [NotificationController::class, 'summary']);
    Route::get('/notifications/messages', [NotificationController::class, 'messages']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);

    // Subscriptions
    Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    Route::get('/subscription/usage', [SubscriptionController::class, 'usage']);

    // Seller applications (protected routes)
    Route::post('/seller/apply', [SellerController::class, 'apply']);
    Route::get('/seller/application-status', [SellerController::class, 'applicationStatus']);
    Route::get('/seller/dashboard', [SellerController::class, 'dashboard']);

    // Order management
    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'create']);
        Route::get('/my-orders', [OrderController::class, 'getUserOrders']);
        Route::get('/my-sales', [OrderController::class, 'getUserSales']);
        Route::get('/{orderNumber}', [OrderController::class, 'show']);
        Route::put('/{orderNumber}/status', [OrderController::class, 'updateStatus']);
        Route::post('/{orderNumber}/cancel', [OrderController::class, 'cancel']);
    });

    // Payment management
    Route::prefix('payments')->group(function () {
        Route::post('/initialize', [PaymentController::class, 'initializePayment']);
        Route::post('/verify', [PaymentController::class, 'verifyPayment']);
        Route::get('/history', [PaymentController::class, 'getPaymentHistory']);
        Route::get('/config', [PaymentController::class, 'getGatewayConfig']);
        Route::get('/{reference}', [PaymentController::class, 'getPaymentDetails']);
    });

    // User invoice management
    Route::prefix('user')->group(function () {
        Route::get('/invoices', [AdminController::class, 'getUserInvoices']);
        Route::get('/invoices/{id}', [AdminController::class, 'getUserInvoice']);
        Route::post('/invoices/{id}/mark-paid', [AdminController::class, 'markInvoiceAsPaid']);
        Route::post('/invoices/{id}/payment-proof', [AdminController::class, 'submitPaymentProof']);
        Route::get('/invoices/{id}/download', [AdminController::class, 'downloadUserInvoice']);
    });

    // Banner Purchase Routes
    Route::prefix('banner-purchase')->group(function () {
        Route::post('/request', [BannerPurchaseController::class, 'createPurchaseRequest']);
        Route::get('/my-requests', [BannerPurchaseController::class, 'getUserPurchaseRequests']);
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
    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        // Listing management
        Route::get('/listings', [AdminController::class, 'listings']);
        Route::post('/listings/{id}/approve', [AdminController::class, 'approveListing']);
        Route::post('/listings/{id}/reject', [AdminController::class, 'rejectListing']);
        Route::delete('/listings/{id}', [AdminController::class, 'deleteListing']);
        Route::post('/listings/{id}/feature', [AdminController::class, 'featureListing']);

        // Listing moderation
        Route::get('/listings/moderation', [AdminController::class, 'getListingsForModeration']);
        Route::post('/listings/{id}/moderate', [AdminController::class, 'moderateListing']);
        Route::post('/listings/{id}/extend', [AdminController::class, 'extendListingExpiration']);
        Route::post('/listings/cleanup', [AdminController::class, 'runAutoCleanup']);
        Route::get('/listings/moderation/stats', [AdminController::class, 'getModerationStats']);

        // Priority and featured listing management
        Route::put('/listings/{id}/priority', [AdminController::class, 'updateListingPriority']);
        Route::put('/listings/{id}/featured', [AdminController::class, 'updateFeaturedStatus']);
        Route::put('/listings/priority/bulk', [AdminController::class, 'bulkUpdatePriority']);
        Route::get('/listings/priority/stats', [AdminController::class, 'getPriorityStatistics']);
        Route::get('/listings/featured/stats', [AdminController::class, 'getFeaturedStatistics']);

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
        Route::get('/banners/active', [BannerController::class, 'active']);
        Route::get('/banners/revenue-analytics', [AdminController::class, 'getBannerRevenueAnalytics']);
        Route::get('/banners/pricing-tiers', [AdminController::class, 'getBannerPricingTiers']);
        Route::apiResource('banners', BannerController::class);

        // Banner Purchase Management
        Route::prefix('banner-purchase')->group(function () {
            Route::get('/requests', [BannerPurchaseController::class, 'getAllPurchaseRequests']);
            Route::post('/requests/{id}/confirm-payment', [BannerPurchaseController::class, 'confirmPayment']);
            Route::post('/requests/{id}/create-banner', [BannerPurchaseController::class, 'createBannerFromRequest']);
        });

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
        Route::post('/invoices/generate-for-application', [AdminController::class, 'generateInvoiceForApplication']);
        Route::post('/invoices/{id}/send', [AdminController::class, 'sendSellerInvoice']);
        Route::get('/invoices/{id}/download', [AdminController::class, 'downloadInvoice']);
        Route::post('/invoices/{id}/mark-paid', [AdminController::class, 'markInvoiceAsPaid']);
        Route::post('/invoices/sync-transactions', [AdminController::class, 'syncInvoiceTransactions']);
        Route::post('/invoices/{id}/approve-payment', [AdminController::class, 'approvePayment']);
        Route::get('/documents/download', [AdminController::class, 'downloadSellerDocument']);

        // Subscription Plans Management
        Route::get('/subscription-plans', [AdminController::class, 'getSubscriptionPlans']);
        Route::get('/subscription-plans/{id}/stats', [AdminController::class, 'getSubscriptionPlanStats']);
        Route::post('/subscription-plans', [AdminController::class, 'createSubscriptionPlan']);
        Route::put('/subscription-plans/{id}', [AdminController::class, 'updateSubscriptionPlan']);
        Route::delete('/subscription-plans/{id}', [AdminController::class, 'deleteSubscriptionPlan']);

        // Financial Management Routes
        Route::prefix('financial')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\Api\FinancialController::class, 'getFinancialStats']);
            Route::get('/revenue', [\App\Http\Controllers\Api\FinancialController::class, 'getRevenueSummary']);
            Route::get('/expenses', [\App\Http\Controllers\Api\FinancialController::class, 'getExpenseSummary']);
            Route::post('/expenses', [\App\Http\Controllers\Api\FinancialController::class, 'createExpense']);
            Route::patch('/expenses/{id}', [\App\Http\Controllers\Api\FinancialController::class, 'updateExpense']);
            Route::delete('/expenses/{id}', [\App\Http\Controllers\Api\FinancialController::class, 'deleteExpense']);
            Route::get('/export', [\App\Http\Controllers\Api\FinancialController::class, 'exportFinancialReport']);
            Route::get('/transactions', [\App\Http\Controllers\Api\FinancialController::class, 'getTransactions']);
            Route::get('/trends', [\App\Http\Controllers\Api\FinancialController::class, 'getMonthlyTrends']);
            Route::get('/service-templates', [\App\Http\Controllers\Api\FinancialController::class, 'getServiceTemplates']);
            Route::get('/revenue-breakdown', [\App\Http\Controllers\Api\FinancialController::class, 'getRevenueBreakdown']);
            Route::post('/export-report', [\App\Http\Controllers\Api\FinancialController::class, 'exportReport']);
        });

        // Customer & Supplier Management Routes
        Route::get('/customers', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'getCustomers']);
        Route::get('/customers/stats', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'getCustomerStats']);
        Route::get('/customers/{id}', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'getCustomerDetails']);
        Route::post('/customers', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'createOrUpdateCustomer']);
        Route::put('/customers/{id}', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'createOrUpdateCustomer']);
        Route::get('/suppliers', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'getSuppliers']);
        Route::get('/suppliers/stats', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'getSupplierStats']);
        Route::get('/suppliers/{id}', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'getSupplierDetails']);
        Route::post('/suppliers', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'createOrUpdateSupplier']);
        Route::put('/suppliers/{id}', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'createOrUpdateSupplier']);
        Route::delete('/customers/{id}', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'deleteCustomer']);
        Route::delete('/suppliers/{id}', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'deleteSupplier']);
        Route::post('/export-customers-suppliers', [\App\Http\Controllers\Api\CustomerSupplierController::class, 'exportData']);

        // Admin Messaging Routes
        Route::prefix('messages')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AdminMessagingController::class, 'getAdminMessages']);
            Route::post('/', [\App\Http\Controllers\Api\AdminMessagingController::class, 'sendAdminMessage']);
            Route::post('/broadcast', [\App\Http\Controllers\Api\AdminMessagingController::class, 'sendBroadcast']);
            Route::post('/mark-read', [\App\Http\Controllers\Api\AdminMessagingController::class, 'markAsRead']);
            Route::delete('/', [\App\Http\Controllers\Api\AdminMessagingController::class, 'deleteMessages']);
        });
        Route::get('/conversations', [\App\Http\Controllers\Api\AdminMessagingController::class, 'getSystemConversations']);
        Route::get('/conversations/{id}', [\App\Http\Controllers\Api\AdminMessagingController::class, 'getSystemConversation']);
        Route::patch('/conversations/{id}/read', [\App\Http\Controllers\Api\AdminMessagingController::class, 'markConversationAsRead']);
        Route::get('/email-queue', [\App\Http\Controllers\Api\AdminMessagingController::class, 'getEmailQueueStatus']);
        Route::post('/email-queue/process', [\App\Http\Controllers\Api\AdminMessagingController::class, 'processEmailQueue']);

        // Category Management Admin Routes
        Route::get('/categories/stats', [CategoryController::class, 'getStats']);
        Route::apiResource('categories', CategoryController::class);

        // System Status Endpoint
        Route::get('/system/status', [AdminController::class, 'getSystemStatus']);

        // Admin inquiry management
        Route::get('/inquiries/{id}', [InquiryController::class, 'show']);
        Route::put('/inquiries/{id}', [InquiryController::class, 'update']);
        Route::get('/listings/{listingId}/inquiries', [InquiryController::class, 'getForListing']);


        // System Monitor Routes
        Route::prefix('system')->group(function () {
            Route::get('/metrics', [SystemMonitorController::class, 'getSystemMetrics']);
            Route::get('/application-metrics', [SystemMonitorController::class, 'getApplicationMetrics']);
            Route::get('/health', [SystemMonitorController::class, 'getHealthStatus']);
            Route::get('/logs', [SystemMonitorController::class, 'getSystemLogs']);
        });

        // Communication Managment
        Route::group(['prefix' => '/communication', 'as' => 'communication.'], function () {
            Route::group(['prefix' => '/newsletters', 'as' => 'newsletters.'], function () {
                Route::get('/', [NewsLetterController::class, 'index']);
                Route::post('/store', [NewsLetterController::class, 'store']);
                Route::get('/show/{id}', [NewsLetterController::class, 'show']);
                Route::put('/update/{id}', [NewsLetterController::class, 'update']);
                Route::delete('/delete/{id}', [NewsLetterController::class, 'destroy']);
                Route::post('/send/{id}', [NewsLetterController::class, 'send']);
                Route::post('/duplicate/{id}', [NewsLetterController::class, 'duplicate']);
            });

            Route::group(['prefix' => '/newsletter-templates', 'as' => 'newsletter-templates.'], function () {
                Route::get('/', [NewsLetterTemplateController::class, 'index']);
                Route::post('/store', [NewsLetterTemplateController::class, 'store']);
                Route::get('/show/{id}', [NewsLetterTemplateController::class, 'show']);
                Route::put('/update/{id}', [NewsLetterTemplateController::class, 'update']);
                Route::delete('/delete/{id}', [NewsLetterTemplateController::class, 'destroy']);
                Route::post('/duplicate/{id}', [NewsLetterTemplateController::class, 'duplicate']);
                Route::get('/preview/{id}', [NewsLetterTemplateController::class, 'preview']);
            });

            Route::group(['prefix' => '/newsletter-settings', 'as' => 'newsletter-settings.'], function () {
                Route::get('/', [NewsletterSettingsController::class, 'index']);
                Route::put('/update', [NewsletterSettingsController::class, 'update']);
                Route::get('/automation-status', [NewsletterSettingsController::class, 'getAutomationStatus']);
                Route::post('/toggle-automation', [NewsletterSettingsController::class, 'toggleAutomation']);
            });

            Route::group(['prefix' => '/email-configs', 'as' => 'email-configs.'], function () {
                Route::get('/', [EmailConfigController::class, 'index']);
                Route::post('/store', [EmailConfigController::class, 'store']);
                Route::get('/show/{driver}', [EmailConfigController::class, 'show']);
                Route::put('/update/{driver}', [EmailConfigController::class, 'update']);
                Route::post('/test/{driver}', [EmailConfigController::class, 'test']);
            });
        });

        // Knowledge Base Management
        Route::prefix('knowledge-base')->group(function () {
            Route::get('/', [KnowledgeBaseController::class, 'indexAdmin']);
            Route::post('/', [KnowledgeBaseController::class, 'store']);
            Route::get('/categories', [KnowledgeBaseController::class, 'categoriesAdmin']);
            Route::get('/statistics', [KnowledgeBaseController::class, 'statistics']);
            Route::put('/{id}', [KnowledgeBaseController::class, 'update']);
            Route::delete('/{id}', [KnowledgeBaseController::class, 'destroy']);
        });

        // Newsletter Management (uses Communication/NewsLetterController)
        Route::prefix('newsletters')->group(function () {
            Route::get('/', [NewsLetterController::class, 'index']);
            Route::post('/', [NewsLetterController::class, 'store']);
            Route::get('/stats', [NewsLetterController::class, 'getStats']);
            Route::get('/settings', [NewsLetterController::class, 'getSettings']);
            Route::put('/settings', [NewsLetterController::class, 'updateSettings']);
            Route::get('/{id}', [NewsLetterController::class, 'show']);
            Route::put('/{id}', [NewsLetterController::class, 'update']);
            Route::delete('/{id}', [NewsLetterController::class, 'destroy']);
            Route::post('/{id}/send', [NewsLetterController::class, 'send']);
            Route::post('/{id}/duplicate', [NewsLetterController::class, 'duplicate']);
        });

        // Newsletter Templates Management
        Route::prefix('newsletter-templates')->group(function () {
            Route::get('/', [NewsLetterTemplateController::class, 'index']);
            Route::post('/', [NewsLetterTemplateController::class, 'store']);
            Route::get('/{id}', [NewsLetterTemplateController::class, 'show']);
            Route::put('/{id}', [NewsLetterTemplateController::class, 'update']);
            Route::delete('/{id}', [NewsLetterTemplateController::class, 'destroy']);
            Route::post('/{id}/duplicate', [NewsLetterTemplateController::class, 'duplicate']);
            Route::get('/{id}/preview', [NewsLetterTemplateController::class, 'preview']);
        });

        // Newsletter Settings Management
        Route::prefix('newsletter-settings')->group(function () {
            Route::get('/', [NewsLetterController::class, 'getSettings']);
            Route::put('/', [NewsLetterController::class, 'updateSettings']);
        });

        // Newsletter Subscribers Management
        Route::prefix('newsletter-subscribers')->group(function () {
            Route::get('/', [NewsletterSubscriberController::class, 'index']);
            Route::post('/', [NewsletterSubscriberController::class, 'store']);
            Route::get('/stats', [NewsletterSubscriberController::class, 'getStats']);
        });

        // Email Configuration
        Route::prefix('email-configuration')->group(function () {
            Route::get('/', [EmailConfigurationController::class, 'index']);
            Route::post('/', [EmailConfigurationController::class, 'store']);
            Route::put('/{id}', [EmailConfigurationController::class, 'update']);
            Route::delete('/{id}', [EmailConfigurationController::class, 'destroy']);
            Route::post('/test', [EmailConfigurationController::class, 'testConfiguration']);
            Route::get('/status', [EmailConfigurationController::class, 'getStatus']);
        });

        // Financial Transactions
        Route::prefix('financial-transactions')->group(function () {
            Route::get('/', [FinancialTransactionController::class, 'getTransactions']);
            Route::post('/', [FinancialTransactionController::class, 'store']);
            Route::put('/{id}', [FinancialTransactionController::class, 'update']);
            Route::delete('/{id}', [FinancialTransactionController::class, 'destroy']);
            Route::get('/summary', [FinancialTransactionController::class, 'getSummary']);
            Route::get('/monthly-trends', [FinancialTransactionController::class, 'getMonthlyTrends']);
            Route::get('/category-stats', [FinancialTransactionController::class, 'getCategoryStats']);
            Route::post('/reconcile', [FinancialTransactionController::class, 'reconcile']);
        });
    });
});
