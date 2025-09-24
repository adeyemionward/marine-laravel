<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\FinancialController;
use Exception;

class TestFinancialLogging extends Command
{
    protected $signature = 'test:financial-logging';
    protected $description = 'Test the enhanced financial logging system';

    public function handle()
    {
        $this->info('Testing Financial Logging System...');

        // Test financial channel logging
        Log::channel('financial')->info('Financial logging test started', [
            'test_id' => uniqid('TEST_'),
            'timestamp' => now()->toISOString(),
            'module' => 'financial_management',
            'operation' => 'test_logging'
        ]);

        // Test audit channel logging
        Log::channel('audit')->info('Audit logging test', [
            'action' => 'test_audit_log',
            'user' => 'system',
            'timestamp' => now()->toISOString()
        ]);

        // Test error logging structure
        $testError = new Exception('This is a test error for logging validation');

        $errorId = uniqid('ERR_');
        $errorContext = [
            'error_id' => $errorId,
            'error_code' => 'TEST_ERROR',
            'test_data' => [
                'operation' => 'test_financial_logging',
                'expected_behavior' => 'structured_error_logging'
            ],
            'exception_class' => get_class($testError),
            'file' => $testError->getFile(),
            'line' => $testError->getLine()
        ];

        Log::channel('financial')->error('Test error for financial module', $errorContext);

        $this->info('✅ Financial logging test completed');
        $this->info("📁 Check logs at:");
        $this->info("   - storage/logs/financial/financial-" . now()->format('Y-m-d') . ".log");
        $this->info("   - storage/logs/audit/audit-" . now()->format('Y-m-d') . ".log");
        $this->info("   - storage/logs/laravel.log");

        $this->info("🔍 Error ID for tracking: {$errorId}");

        return 0;
    }
}