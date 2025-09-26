<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DatabaseMaintenanceController extends Controller
{

    public function systemHealthOverview()
    {
        // Database name
        $dbName = DB::getDatabaseName();

        // Number of tables
        $tablesCount = DB::select("SHOW TABLES");
        $tablesCount = count($tablesCount);

        // Number of stored functions
            $functionsCount = DB::table('information_schema.routines')
        ->where('routine_schema', $dbName)
        ->where('routine_type', 'FUNCTION')
        ->count();

        // Database size (in MB)
        $dbSize = DB::select("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size
            FROM information_schema.tables
            WHERE table_schema = ?
        ", [$dbName]);
        $dbSize = $dbSize[0]->size ?? 0;

        // Performance metrics (simplified placeholders)
        $slowQueries = DB::select("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
        $slowQueries = $slowQueries[0]->Value ?? 0;

        $indexUsage = DB::select("SHOW GLOBAL STATUS LIKE 'Handler_read_rnd_next'");
        $indexUsage = $indexUsage[0]->Value > 0 ? "95%" : "0%"; // simplified demo

        return response()->json([
            'status' => 'success',
            'system_health' => [
                'database_health' => [
                    'tables' => $tablesCount,
                    'functions' => $functionsCount,
                    'database_size' => $dbSize . ' MB',
                ],
                'performance_metrics' => [
                    'slow_queries' => $slowQueries,
                    'index_usage' => $indexUsage,
                    'database_size' => $dbSize . ' MB',
                ]
            ]
        ]);
    }

    /**
     * Fetch Maintenance Logs
     */
    public function getMaintenanceLogs(Request $request)
    {
        $logsFile = storage_path('logs/laravel.log');

        if (!file_exists($logsFile)) {
            return response()->json([
                'status' => 'success',
                'logs' => [],
                'message' => 'No log file found.'
            ]);
        }

        $logs = file($logsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Reverse the logs so latest entries are first
        $logs = array_reverse($logs);

        // Pagination parameters
        $page = max((int) $request->query('page', 1), 1);
        $perPage = max((int) $request->query('per_page', 50), 1);

        $total = count($logs);
        $totalPages = ceil($total / $perPage);

        $logsPage = array_slice($logs, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'status' => 'success',
            'current_page' => $page,
            'per_page' => $perPage,
            'total_logs' => $total,
            'total_pages' => $totalPages,
            'logs' => $logsPage
        ]);
    }


    /**
     * Optimize Database
     */
   public function optimizeDatabase()
    {
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            $tableArray = (array) $table;
            $name = array_values($tableArray)[0]; // safer than reset()
            DB::statement("OPTIMIZE TABLE {$name}");
        }

        Log::info("Database optimized on " . Carbon::now());

        return response()->json(['status' => 'success', 'message' => 'Database optimized']);
    }


    /**
     * Cleanup Expired Banners
     */
    public function cleanupExpiredBanners()
    {
        DB::table('banners')
            ->where('expires_at', '<', Carbon::now())
            ->delete();

        Log::info("Expired banners cleaned up on " . Carbon::now());

        return response()->json(['status' => 'success', 'message' => 'Expired banners removed']);
    }

    /**
     * Refresh Metrics
     */
    public function refreshMetrics()
    {
        // Example: refresh a metrics table
        DB::table('metrics')->update(['last_refreshed_at' => Carbon::now()]);

        Log::info("Metrics refreshed on " . Carbon::now());

        return response()->json(['status' => 'success', 'message' => 'Metrics refreshed']);
    }

    /**
     * Remove Orphaned Records & Optimize
     */
    // public function cleanupDatabase()
    // {
    //     // Example: remove users without related profiles
    //     DB::table('users')
    //         ->whereNotIn('id', DB::table('profiles')->pluck('user_id'))
    //         ->delete();

    //     $this->optimizeDatabase();

    //     Log::info("Database cleanup performed on " . Carbon::now());

    //     return response()->json(['status' => 'success', 'message' => 'Orphaned records removed and database optimized']);
    // }


    public function cleanupDatabase()
    {
        $now = Carbon::now();
        $threshold = $now->subDays(30);

        // 1. Delete users who haven't verified email in 30 days
        $deletedUsersCount = DB::table('users')
            ->whereNull('email_verified_at')
            ->where('created_at', '<', $threshold)
            ->delete();

        // 2. Delete old jobs, failed jobs, and job caches
        DB::table('jobs')->where('created_at', '<', $threshold)->delete();
        DB::table('failed_jobs')->where('failed_at', '<', $threshold)->delete();

        // 3. Optimize all tables
        $this->optimizeDatabase();

        // 4. Log the cleanup
        Log::info("Database cleanup performed on " . Carbon::now() . ". Deleted users: " . $deletedUsersCount);

        // 5. Return JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Database cleanup completed successfully'
        ]);
    }

    /**
     * Rebuild Indexes
     */
    public function rebuildIndexes()
    {
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            $name = array_values((array)$table)[0]; // get table name safely
            DB::statement("ALTER TABLE {$name} ENGINE=InnoDB");
        }

        Log::info("Indexes rebuilt on " . Carbon::now());

        return response()->json([
            'status' => 'success',
            'message' => 'Indexes rebuilt'
        ]);
    }

}
