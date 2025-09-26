<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\LoggingService;

class LoggingServiceProvider extends ServiceProvider
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
        $this->configureQueryLogging();
        $this->configureGlobalErrorHandling();
    }

    /**
     * Configure database query logging for slow queries
     */
    protected function configureQueryLogging(): void
    {
        if (config('database.log_slow_queries', false)) {
            $slowQueryThreshold = config('database.slow_query_threshold', 2000); // 2 seconds default

            DB::listen(function ($query) use ($slowQueryThreshold) {
                if ($query->time > $slowQueryThreshold) {
                    LoggingService::logSlowQuery(
                        $query->sql,
                        $query->bindings,
                        $query->time
                    );
                }
            });
        }
    }

    /**
     * Configure global error handling improvements
     */
    protected function configureGlobalErrorHandling(): void
    {
        // Log uncaught exceptions with proper context
        $this->app->singleton(\App\Exceptions\ApiExceptionHandler::class);

        // Add custom log formatters for different environments
        if (config('app.env') === 'production') {
            $this->configureProductionLogging();
        }
    }

    /**
     * Configure production-specific logging
     */
    protected function configureProductionLogging(): void
    {
        // Ensure sensitive data is never logged in production
        config([
            'logging.channels.single.tap' => [
                function ($logger) {
                    $logger->pushProcessor(function ($record) {
                        // Remove sensitive fields from logs
                        if (isset($record['context'])) {
                            $record['context'] = $this->sanitizeLogContext($record['context']);
                        }
                        return $record;
                    });
                }
            ]
        ]);
    }

    /**
     * Sanitize log context to remove sensitive information
     */
    protected function sanitizeLogContext(array $context): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_key', 'secret', 'private_key', 'credit_card',
            'cvv', 'ssn', 'social_security', 'bank_account', 'auth_token'
        ];

        array_walk_recursive($context, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '[REDACTED]';
            }
        });

        return $context;
    }
}