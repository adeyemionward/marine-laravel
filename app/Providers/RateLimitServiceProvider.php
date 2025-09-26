<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for different routes.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limit
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Authentication endpoints (login, register, password reset)
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(20)->by($request->ip()),
            ];
        });

        // Password reset attempts
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(10)->by($request->ip()),
            ];
        });

        // Email verification
        RateLimiter::for('email-verification', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(15)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // File upload endpoints
        RateLimiter::for('uploads', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(50)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Payment processing endpoints
        RateLimiter::for('payments', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(20)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Admin API endpoints (higher limits for authenticated admins)
        RateLimiter::for('admin', function (Request $request) {
            return $request->user() && $request->user()->hasRole('admin')
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(10)->by($request->ip());
        });

        // Search endpoints
        RateLimiter::for('search', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(200)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Listing creation/update
        RateLimiter::for('listings', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(30)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Contact/messaging endpoints
        RateLimiter::for('messages', function (Request $request) {
            return [
                Limit::perMinute(15)->by($request->user()?->id ?: $request->ip()),
                Limit::perHour(50)->by($request->user()?->id ?: $request->ip()),
            ];
        });
    }
}