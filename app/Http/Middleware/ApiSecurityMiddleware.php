<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiSecurityMiddleware
{
    /**
     * Suspicious patterns that might indicate malicious requests
     */
    protected array $suspiciousPatterns = [
        '/\b(union|select|insert|delete|drop|create|alter|exec|script)\b/i',
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
        '/javascript:/i',
        '/vbscript:/i',
        '/onload\s*=/i',
        '/onerror\s*=/i',
    ];

    /**
     * Blocked user agents
     */
    protected array $blockedUserAgents = [
        'bot',
        'spider',
        'crawl',
        'scraper',
        'wget',
        'curl', // Be careful with this in production
    ];

    /**
     * Maximum request size in bytes (10MB)
     */
    protected int $maxRequestSize = 10485760;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check request size
        if ($this->exceedsMaxSize($request)) {
            return $this->securityResponse('Request too large', 413);
        }

        // Check for suspicious patterns
        if ($this->containsSuspiciousPatterns($request)) {
            Log::warning('Suspicious request detected', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'data' => $request->all()
            ]);

            return $this->securityResponse('Invalid request format', 400);
        }

        // Check user agent (be careful with this in production)
        if ($this->hasBlockedUserAgent($request)) {
            return $this->securityResponse('Access denied', 403);
        }

        // Add security headers to response
        $response = $next($request);

        return $this->addSecurityHeaders($response);
    }

    /**
     * Check if request exceeds maximum size
     */
    protected function exceedsMaxSize(Request $request): bool
    {
        $contentLength = $request->header('Content-Length');

        return $contentLength && (int) $contentLength > $this->maxRequestSize;
    }

    /**
     * Check if request contains suspicious patterns
     */
    protected function containsSuspiciousPatterns(Request $request): bool
    {
        $inputs = array_merge(
            $request->all(),
            [$request->getPathInfo(), $request->getQueryString()]
        );

        foreach ($inputs as $input) {
            if (is_string($input)) {
                foreach ($this->suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $input)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if request has blocked user agent
     */
    protected function hasBlockedUserAgent(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        foreach ($this->blockedUserAgents as $blocked) {
            if (str_contains($userAgent, $blocked)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders(Response $response): Response
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'",
        ];

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Create security response
     */
    protected function securityResponse(string $message, int $status): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'status' => $status
        ], $status);
    }
}