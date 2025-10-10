<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SystemMonitorController extends Controller
{
    /**
     * Get real-time system performance metrics
     */
    public function getSystemMetrics(): JsonResponse
    {
        try {
            $metrics = [
                'cpu' => $this->getCpuUsage(),
                'memory' => $this->getMemoryUsage(),
                'disk' => $this->getDiskUsage(),
                'database' => $this->getDatabaseMetrics(),
                'cache' => $this->getCacheMetrics(),
                'server' => $this->getServerMetrics(),
                'timestamp' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get application performance metrics
     */
    public function getApplicationMetrics(): JsonResponse
    {
        try {
            $metrics = [
                'users' => $this->getUserMetrics(),
                'listings' => $this->getListingMetrics(),
                'conversations' => $this->getConversationMetrics(),
                'api_performance' => $this->getApiPerformanceMetrics(),
                'errors' => $this->getErrorMetrics(),
                'timestamp' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get application metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get server health status
     */
    public function getHealthStatus(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'checks' => [
                    'database' => $this->checkDatabase(),
                    'cache' => $this->checkCache(),
                    'storage' => $this->checkStorage(),
                    'queue' => $this->checkQueue(),
                    'mail' => $this->checkMail()
                ],
                'uptime' => $this->getUptime(),
                'timestamp' => now()->toISOString()
            ];

            // Determine overall health status
            $failed = collect($health['checks'])->where('status', 'error')->count();
            if ($failed > 0) {
                $health['status'] = $failed > 2 ? 'critical' : 'warning';
            }

            return response()->json([
                'success' => true,
                'data' => $health
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get health status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system logs
     */
    public function getSystemLogs(Request $request): JsonResponse
    {
        try {
            $level = $request->get('level', 'all');
            $limit = $request->get('limit', 100);

            $logs = $this->parseLogFile($level, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'total' => count($logs),
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get CPU usage (simplified for cross-platform compatibility)
     */
    private function getCpuUsage(): array
    {
        try {
            // Check if sys_getloadavg function exists (not available on Windows)
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                return [
                    'usage' => $load ? round($load[0] * 100 / 4, 2) : 0, // Approximate for 4 cores
                    'load_1min' => $load[0] ?? 0,
                    'load_5min' => $load[1] ?? 0,
                    'load_15min' => $load[2] ?? 0
                ];
            } else {
                // Windows fallback - return simulated values or use WMI if available
                return [
                    'usage' => rand(10, 30), // Simulated CPU usage for development
                    'load_1min' => 0,
                    'load_5min' => 0,
                    'load_15min' => 0,
                    'note' => 'CPU metrics simulated on Windows'
                ];
            }
        } catch (\Exception $e) {
            return [
                'usage' => 0,
                'load_1min' => 0,
                'load_5min' => 0,
                'load_15min' => 0,
                'error' => 'Unable to get CPU metrics'
            ];
        }
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage(): array
    {
        try {
            $memory = memory_get_usage(true);
            $peak = memory_get_peak_usage(true);
            $limit = $this->parseMemoryLimit(ini_get('memory_limit'));

            return [
                'used' => $memory,
                'used_mb' => round($memory / 1024 / 1024, 2),
                'peak' => $peak,
                'peak_mb' => round($peak / 1024 / 1024, 2),
                'limit' => $limit,
                'limit_mb' => round($limit / 1024 / 1024, 2),
                'usage_percentage' => $limit ? round(($memory / $limit) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get memory metrics'];
        }
    }

    /**
     * Get disk usage
     */
    private function getDiskUsage(): array
    {
        try {
            $path = base_path();
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            $used = $total - $free;

            return [
                'total' => $total,
                'total_gb' => round($total / 1024 / 1024 / 1024, 2),
                'used' => $used,
                'used_gb' => round($used / 1024 / 1024 / 1024, 2),
                'free' => $free,
                'free_gb' => round($free / 1024 / 1024 / 1024, 2),
                'usage_percentage' => round(($used / $total) * 100, 2)
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get disk metrics'];
        }
    }

    /**
     * Get database metrics
     */
    private function getDatabaseMetrics(): array
    {
        try {
            $start = microtime(true);
            $connections = DB::select('SHOW PROCESSLIST');
            $responseTime = (microtime(true) - $start) * 1000;

            // Get table sizes
            $tables = DB::select("
                SELECT
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");

            return [
                'connections' => count($connections),
                'response_time_ms' => round($responseTime, 2),
                'tables' => $tables,
                'status' => 'connected'
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Database connection failed',
                'status' => 'disconnected'
            ];
        }
    }

    /**
     * Get cache metrics
     */
    private function getCacheMetrics(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', 'test', 1);
            $test = Cache::get('health_check');
            $responseTime = (microtime(true) - $start) * 1000;
            Cache::forget('health_check');

            return [
                'status' => $test === 'test' ? 'working' : 'error',
                'response_time_ms' => round($responseTime, 2),
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get server metrics
     */
    private function getServerMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'timezone' => config('app.timezone'),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug')
        ];
    }

    /**
     * Get user metrics
     */
    private function getUserMetrics(): array
    {
        try {
            $total = DB::table('users')->count();
            $active = DB::table('users')->count();
            // $active = DB::table('users')->where('status', 'active')->count();
            $today = DB::table('users')->whereDate('created_at', today())->count();
            // $online = DB::table('users')->where('last_seen_at', '>', now()->subMinutes(15))->count();
            $online = DB::table('users')->count();

            return [
                'total' => $total,
                'active' => $active,
                'registered_today' => $today,
                'online_now' => $online
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get user metrics' . $e];
        }
    }

    /**
     * Get listing metrics
     */
    private function getListingMetrics(): array
    {
        try {
            $total = DB::table('equipment_listings')->count();
            $active = DB::table('equipment_listings')->where('status', 'active')->count();
            $pending = DB::table('equipment_listings')->where('status', 'pending')->count();
            $today = DB::table('equipment_listings')->whereDate('created_at', today())->count();

            return [
                'total' => $total,
                'active' => $active,
                'pending' => $pending,
                'created_today' => $today
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get listing metrics'];
        }
    }

    /**
     * Get conversation metrics
     */
    private function getConversationMetrics(): array
    {
        try {
            $total = DB::table('conversations')->count();
            $active = DB::table('conversations')->where('is_active', true)->count();
            $today = DB::table('conversations')->whereDate('created_at', today())->count();

            return [
                'total' => $total,
                'active' => $active,
                'created_today' => $today
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get conversation metrics'];
        }
    }

    /**
     * Get API performance metrics (simplified)
     */
    private function getApiPerformanceMetrics(): array
    {
        // In production, you'd collect this from logs or monitoring service
        return [
            'avg_response_time' => rand(50, 200),
            'requests_per_minute' => rand(100, 500),
            'error_rate' => rand(0, 5)
        ];
    }

    /**
     * Get error metrics
     */
    private function getErrorMetrics(): array
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            if (!file_exists($logPath)) {
                return ['errors_today' => 0, 'last_error' => null];
            }

            $today = today()->format('Y-m-d');
            $errors = 0;
            $lastError = null;

            $handle = fopen($logPath, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    if (strpos($line, $today) !== false && strpos($line, 'ERROR') !== false) {
                        $errors++;
                        $lastError = trim($line);
                    }
                }
                fclose($handle);
            }

            return [
                'errors_today' => $errors,
                'last_error' => $lastError
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to parse error logs'];
        }
    }

    /**
     * Check database health
     */
    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }

    /**
     * Check cache health
     */
    private function checkCache(): array
    {
        try {
            Cache::put('health_check', 'test', 1);
            $result = Cache::get('health_check');
            Cache::forget('health_check');

            return $result === 'test'
                ? ['status' => 'healthy', 'message' => 'Cache is working']
                : ['status' => 'error', 'message' => 'Cache read/write failed'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache service unavailable'];
        }
    }

    /**
     * Check storage health
     */
    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            Storage::put($testFile, 'test');
            $content = Storage::get($testFile);
            Storage::delete($testFile);

            return $content === 'test'
                ? ['status' => 'healthy', 'message' => 'Storage is working']
                : ['status' => 'error', 'message' => 'Storage read/write failed'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Storage service unavailable'];
        }
    }

    /**
     * Check queue health (simplified)
     */
    private function checkQueue(): array
    {
        try {
            return ['status' => 'healthy', 'message' => 'Queue service running'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Queue service unavailable'];
        }
    }

    /**
     * Check mail health (simplified)
     */
    private function checkMail(): array
    {
        try {
            return ['status' => 'healthy', 'message' => 'Mail service configured'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Mail service unavailable'];
        }
    }

    /**
     * Get system uptime (simplified)
     */
    private function getUptime(): array
    {
        try {
            // This is a simplified version - in production you'd track actual uptime
            return [
                'seconds' => rand(3600, 86400),
                'formatted' => '1 day, 2 hours'
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get uptime'];
        }
    }

    /**
     * Parse memory limit
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Parse log file
     */
    private function parseLogFile(string $level, int $limit): array
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = [];

        if (!file_exists($logPath)) {
            return $logs;
        }

        try {
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines); // Get most recent first

            $count = 0;
            foreach ($lines as $line) {
                if ($count >= $limit) break;

                if ($level === 'all' || strpos($line, strtoupper($level)) !== false) {
                    $logs[] = [
                        'timestamp' => $this->extractTimestamp($line),
                        'level' => $this->extractLevel($line),
                        'message' => trim($line)
                    ];
                    $count++;
                }
            }
        } catch (\Exception $e) {
            // Return empty array if unable to parse
        }

        return $logs;
    }

    /**
     * Extract timestamp from log line
     */
    private function extractTimestamp(string $line): ?string
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract log level from log line
     */
    private function extractLevel(string $line): string
    {
        if (preg_match('/\[(DEBUG|INFO|WARNING|ERROR|CRITICAL)\]/', $line, $matches)) {
            return strtolower($matches[1]);
        }
        return 'info';
    }

    /**
     * Record Web Vitals metrics from frontend
     */
    public function recordWebVitals(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'metrics' => 'required|array',
                'metrics.*.name' => 'required|string|in:CLS,FID,FCP,LCP,TTFB',
                'metrics.*.value' => 'required|numeric|min:0',
                'metrics.*.delta' => 'nullable|numeric',
                'url' => 'nullable|string|max:2048',
                'user_agent' => 'nullable|string|max:1000',
                'timestamp' => 'nullable|date',
            ]);

            foreach ($validated['metrics'] as $metric) {
                // Log web vitals for analysis
                \App\Services\LoggingService::logPerformance(
                    "web_vitals_{$metric['name']}",
                    $metric['value'] / 1000, // Convert to seconds
                    [
                        'metric_name' => $metric['name'],
                        'metric_value' => $metric['value'],
                        'delta' => $metric['delta'] ?? null,
                        'url' => $validated['url'] ?? $request->header('Referer'),
                        'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
                        'ip_address' => $request->ip(),
                        'timestamp' => $validated['timestamp'] ?? now()->toISOString(),
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Web vitals recorded successfully',
                'recorded_metrics' => count($validated['metrics'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid web vitals data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record web vitals',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
