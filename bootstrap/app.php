<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS middleware
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Add API security and rate limiting
        $middleware->api(append: [
            \App\Http\Middleware\RequestIdMiddleware::class,
            \App\Http\Middleware\ApiSecurityMiddleware::class,
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Handle CORS for web routes
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Add rate limiting with different tiers
        $middleware->throttleApi('api');

        // Register custom middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'api.rate_limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
            'api.security' => \App\Http\Middleware\ApiSecurityMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Endpoint not found',
                'status' => 404
            ], 404);
        });

        // Handle wrong HTTP method (GET instead of POST, etc.)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            return response()->json([
                'message' => 'HTTP method not allowed for this endpoint',
                'status' => 405
            ], 405);
        });

        // Handle all other errors
        $exceptions->render(function (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Server error occurred',
                'status' => 500
            ], 500);
        });
    })->create();
