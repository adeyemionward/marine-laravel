<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoggingService
{
    /**
     * Log authentication events
     */
    public static function logAuthentication(string $action, array $context = []): void
    {
        $baseContext = [
            'category' => 'authentication',
            'action' => $action,
            'user_id' => Auth::id(),
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("Auth: {$action}", array_merge($baseContext, $context));
    }

    /**
     * Log payment-related events
     */
    public static function logPayment(string $action, array $context = []): void
    {
        $baseContext = [
            'category' => 'payment',
            'action' => $action,
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("Payment: {$action}", array_merge($baseContext, $context));
    }

    /**
     * Log equipment listing events
     */
    public static function logListing(string $action, array $context = []): void
    {
        $baseContext = [
            'category' => 'listing',
            'action' => $action,
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("Listing: {$action}", array_merge($baseContext, $context));
    }

    /**
     * Log subscription events
     */
    public static function logSubscription(string $action, array $context = []): void
    {
        $baseContext = [
            'category' => 'subscription',
            'action' => $action,
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("Subscription: {$action}", array_merge($baseContext, $context));
    }

    /**
     * Log security events
     */
    public static function logSecurity(string $action, array $context = []): void
    {
        $baseContext = [
            'category' => 'security',
            'action' => $action,
            'user_id' => Auth::id(),
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::warning("Security: {$action}", array_merge($baseContext, $context));
    }

    /**
     * Log business events (general)
     */
    public static function logBusiness(string $action, array $context = []): void
    {
        $baseContext = [
            'category' => 'business',
            'action' => $action,
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
        ];

        Log::info("Business: {$action}", array_merge($baseContext, $context));
    }

    /**
     * Log performance metrics
     */
    public static function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $baseContext = [
            'category' => 'performance',
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toISOString(),
        ];

        $level = $duration > 5.0 ? 'warning' : 'info';

        Log::{$level}("Performance: {$operation}", array_merge($baseContext, $context));
    }

    /**
     * Log API usage for analytics
     */
    public static function logApiUsage(Request $request, int $responseStatus, float $duration = null): void
    {
        $context = [
            'category' => 'api_usage',
            'method' => $request->method(),
            'endpoint' => $request->getPathInfo(),
            'status_code' => $responseStatus,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        if ($duration !== null) {
            $context['duration_ms'] = round($duration * 1000, 2);
        }

        // Add query parameters for GET requests
        if ($request->isMethod('GET') && $request->query()) {
            $context['query_params'] = $request->query();
        }

        Log::info('API Usage', $context);
    }

    /**
     * Log database query performance
     */
    public static function logSlowQuery(string $sql, array $bindings, float $duration): void
    {
        $context = [
            'category' => 'database',
            'action' => 'slow_query',
            'sql' => $sql,
            'bindings' => $bindings,
            'duration_ms' => round($duration, 2),
            'threshold_exceeded' => true,
            'timestamp' => now()->toISOString(),
        ];

        Log::warning('Database: Slow Query Detected', $context);
    }

    /**
     * Log file upload events
     */
    public static function logFileUpload(string $filename, int $size, string $mimeType, bool $success = true): void
    {
        $context = [
            'category' => 'file_upload',
            'filename' => $filename,
            'size_bytes' => $size,
            'mime_type' => $mimeType,
            'success' => $success,
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
        ];

        $level = $success ? 'info' : 'error';
        Log::{$level}('File Upload', $context);
    }

    /**
     * Log email sending events
     */
    public static function logEmail(string $action, string $recipient, string $subject, bool $success = true): void
    {
        $context = [
            'category' => 'email',
            'action' => $action,
            'recipient' => $recipient,
            'subject' => $subject,
            'success' => $success,
            'timestamp' => now()->toISOString(),
        ];

        $level = $success ? 'info' : 'error';
        Log::{$level}("Email: {$action}", $context);
    }
}