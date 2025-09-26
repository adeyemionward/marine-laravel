<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or use existing request ID
        $requestId = $request->header('X-Request-ID') ?? Str::uuid()->toString();

        // Store request ID for use in logging
        config(['logging.channels.single.tap' => [
            function ($logger) use ($requestId) {
                $logger->pushProcessor(function ($record) use ($requestId) {
                    $record['extra']['request_id'] = $requestId;
                    return $record;
                });
            }
        ]]);

        $response = $next($request);

        // Add request ID to response headers
        if ($response instanceof Response) {
            $response->headers->set('X-Request-ID', $requestId);
        }

        return $response;
    }
}