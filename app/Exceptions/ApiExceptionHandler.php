<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Authorization\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApiExceptionHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception): JsonResponse
    {
        // Only handle API requests
        if (!$request->is('api/*')) {
            return parent::render($request, $exception);
        }

        return $this->handleApiException($request, $exception);
    }

    /**
     * Handle API exceptions and return consistent JSON responses
     */
    protected function handleApiException(Request $request, Throwable $exception): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => 'An error occurred',
            'timestamp' => now()->toISOString(),
            'path' => $request->getPathInfo(),
        ];

        // Add request ID for tracing
        $requestId = $request->header('X-Request-ID') ?? uniqid();
        $response['request_id'] = $requestId;

        // Log the exception with context
        $this->logException($exception, $request, $requestId);

        // Handle specific exception types
        switch (true) {
            case $exception instanceof ValidationException:
                $response['message'] = 'Validation failed';
                $response['errors'] = $exception->errors();
                return response()->json($response, 422);

            case $exception instanceof AuthenticationException:
                $response['message'] = 'Authentication required';
                return response()->json($response, 401);

            case $exception instanceof AuthorizationException:
                $response['message'] = 'Insufficient permissions';
                return response()->json($response, 403);

            case $exception instanceof ModelNotFoundException:
                $response['message'] = 'Resource not found';
                return response()->json($response, 404);

            case $exception instanceof NotFoundHttpException:
                $response['message'] = 'Endpoint not found';
                return response()->json($response, 404);

            case $exception instanceof MethodNotAllowedHttpException:
                $response['message'] = 'HTTP method not allowed for this endpoint';
                $response['allowed_methods'] = $exception->getHeaders()['Allow'] ?? [];
                return response()->json($response, 405);

            case $exception instanceof ThrottleRequestsException:
                $response['message'] = 'Too many requests. Please slow down.';
                $response['retry_after'] = $exception->getRetryAfter();
                return response()->json($response, 429);

            case $exception instanceof QueryException:
                $response['message'] = 'Database error occurred';
                if (config('app.debug')) {
                    $response['sql_error'] = $exception->getMessage();
                }
                return response()->json($response, 500);

            default:
                $response['message'] = $this->isHttpException($exception)
                    ? $exception->getMessage()
                    : 'Internal server error';

                if (config('app.debug')) {
                    $response['debug'] = [
                        'exception' => get_class($exception),
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString(),
                    ];
                }

                $statusCode = $this->isHttpException($exception)
                    ? $exception->getStatusCode()
                    : 500;

                return response()->json($response, $statusCode);
        }
    }

    /**
     * Log exception with proper context
     */
    protected function logException(Throwable $exception, Request $request, string $requestId): void
    {
        $context = [
            'request_id' => $requestId,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        // Add request data for non-GET requests (but sanitize sensitive data)
        if (!$request->isMethod('GET')) {
            $requestData = $request->all();
            $this->sanitizeRequestData($requestData);
            $context['request_data'] = $requestData;
        }

        // Different log levels based on exception type
        if ($exception instanceof ValidationException ||
            $exception instanceof AuthenticationException ||
            $exception instanceof AuthorizationException ||
            $exception instanceof NotFoundHttpException) {
            Log::info('API Client Error', $context);
        } else {
            Log::error('API Server Error', $context);
        }
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    protected function sanitizeRequestData(array &$data): void
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_key', 'secret', 'private_key', 'credit_card',
            'cvv', 'ssn', 'social_security', 'bank_account'
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '[REDACTED]';
            }
        });
    }
}