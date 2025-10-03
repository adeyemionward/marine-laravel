<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Backup;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use function PHPUnit\Framework\isEmpty;

class BackupManagementController extends Controller
{
    /**
     * Get Backup Summary + History
     */
    public function getBackups()
    {
        $total = Backup::count();

        // If no backups exist, return a friendly message
        if ($total === 0) {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'summary' => [
                    'total_backups' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'storage_used' => '0 MB'
                ],
                'history' => [],
                'message' => 'No backups found'
            ]);
        }

        $completed = Backup::where('status', 'completed')->count();
        $inProgress = Backup::where('status', 'in_progress')->count();
        $totalSizeMB = Backup::sum('size'); // in MB
        $totalSizeBytes = $totalSizeMB * 1024 * 1024; // Convert to bytes

        $history = Backup::latest()->get();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'summary' => [
                'total_backups' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'storage_used' => round($totalSizeMB, 2) . ' MB',
                'total_size' => round($totalSizeBytes, 0) // Add total_size in bytes for frontend
            ],
            'history' => $history
        ]);
    }



    public function listTables()
    {
        $database = env('DB_DATABASE');

        $tables = DB::select("SHOW TABLES FROM `$database`");

        $key = "Tables_in_$database";

        $tableList = array_map(function($table) use ($key) {
            $name = $table->$key;
            $title = ucwords(str_replace('_', ' ', $name)); // Replace _ with space and capitalize
            return [
                'title' => $title,
                'name' => $name
            ];
        }, $tables);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $tableList
        ]);
    }


    public function createBackup(Request $request)
    {
        $type = $request->input('type', 'full'); // full, incremental, selected_tables, user_data, content_data
        $name = $request->input('name', 'Backup ' . now()->format('Y-m-d H:i:s'));
        $tables = $request->input('tables', []); // used only if type = selected_tables

        $contentTables = [
        'banners',
        'banner_pricing',
        'equipment_categories',
        'equipment_listings',
        'inquiries',
        'invoices',
        'jobs',
        'job_batches',
        'messages',
        'newsletters',
        'newsletter_templates',
        'orders',
        'payments'
        ]; // content_data tables


        $backup = Backup::create([
            'name' => $name,
            'type' => $type,
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
            'created_by' => $request->user()->name ?? 'System Administrator'
        ]);

        // Use backup name for filename (sanitize it)
        $safeFileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
        $fileName = $safeFileName . '_' . now()->format('Y_m_d_His') . '.sql';
        $storageRelativePath = 'backups' . DIRECTORY_SEPARATOR . $fileName;
        $absolutePath = storage_path('app' . DIRECTORY_SEPARATOR . $storageRelativePath);

        $backupsDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        if (!File::isDirectory($backupsDir)) {
            File::makeDirectory($backupsDir, 0775, true);
        }

        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbName = env('DB_DATABASE');
        $dbHost = env('DB_HOST', '127.0.0.1');

        // Try to find mysqldump
        $mysqldumpPath = $this->findMysqldump();

        // If mysqldump is not available, use PHP-based backup
        if (!$mysqldumpPath) {
            return $this->createPhpBackup($backup, $type, $tables, $contentTables, $absolutePath);
        }

        // Build the command based on backup type
        switch ($type) {
            case 'full':
                $command = "\"$mysqldumpPath\" -u{$dbUser}" .
                        ($dbPass !== '' ? " -p{$dbPass}" : '') .
                        " {$dbName} > \"$absolutePath\"";
                break;

            case 'incremental':
                // Local dev/XAMPP usually cannot run incremental backups
                $backup->update([
                    'status' => 'failed',
                    'log_message' => 'Incremental backups are not supported on local/XAMPP environments.',
                    'completed_at' => Carbon::now(),
                    'duration' => 0
                ]);
                return response()->json($backup, 400);

            case 'selected_tables':
                if (empty($tables)) {
                    return response()->json(['error' => 'No tables provided'], 400);
                }
                $tableList = implode(' ', array_map('escapeshellarg', $tables));
                $command = "\"$mysqldumpPath\" -u{$dbUser}" .
                        ($dbPass !== '' ? " -p{$dbPass}" : '') .
                        " {$dbName} {$tableList} > \"$absolutePath\"";
                break;

            case 'user_data':
                $userTables = ['users', 'user_profiles', 'user_favorites', 'user_subscriptions']; // adjust as needed
                $tableList = implode(' ', $userTables);
                $command = "\"$mysqldumpPath\" -u{$dbUser}" .
                        ($dbPass !== '' ? " -p{$dbPass}" : '') .
                        " {$dbName} {$tableList} > \"$absolutePath\"";
                break;

            case 'content_data':
            $tableList = implode(' ', array_map('escapeshellarg', $contentTables));
            $command = "\"$mysqldumpPath\" -u{$dbUser}" .
                    ($dbPass !== '' ? " -p{$dbPass}" : '') .
                    " {$dbName} {$tableList} > \"$absolutePath\"";
            break;

            default:
                return response()->json(['error' => 'Invalid backup type'], 400);
        }

        // Execute the backup and capture both stdout and stderr
        $startTime = microtime(true);
        exec($command . ' 2>&1', $output, $returnVar);
        $duration = round(microtime(true) - $startTime, 2);

        $outputString = implode("\n", $output);

        Log::info('Backup Debug', [
            'command' => $command,
            'returnVar' => $returnVar,
            'output' => $outputString,
            'path' => $absolutePath,
            'file_exists' => File::exists($absolutePath),
            'file_size' => File::exists($absolutePath) ? File::size($absolutePath) : 0,
        ]);

        if ($returnVar === 0 && File::exists($absolutePath) && File::size($absolutePath) > 0) {
            $size = filesize($absolutePath) / 1024 / 1024;

            // Create proper relative path for download
            $relativePath = 'backups/' . basename($absolutePath);

            $backup->update([
                'status' => 'completed',
                'file_path' => $relativePath,
                'size' => round($size, 2),
                'completed_at' => Carbon::now(),
                'duration' => $duration
            ]);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Backup created successfully',
                'data' => $backup
            ]);
        } else {
            $errorMessage = 'Backup command failed. Return code: ' . $returnVar;
            if (!empty($outputString)) {
                $errorMessage .= '. Output: ' . $outputString;
            }

            $backup->update([
                'status' => 'failed',
                'completed_at' => Carbon::now(),
                'duration' => $duration,
                'log_message' => $errorMessage
            ]);

            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Backup creation failed',
                'error' => $errorMessage
            ], 500);
        }
    }


    /**
     * Download Backup
     */
    public function downloadBackup($id)
    {
        $backup = Backup::findOrFail($id);

        if (!$backup->file_path) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Backup file path not found'
            ], 404);
        }

        $filePath = storage_path('app/' . $backup->file_path);

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Backup file does not exist'
            ], 404);
        }

        return response()->download($filePath, basename($filePath));
    }

    /**
     * Delete Backup
     */
    public function deleteBackup($id)
    {
        $backup = Backup::findOrFail($id);

        if ($backup->file_path) {
            $filePath = storage_path('app/' . $backup->file_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $backup->delete();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Backup deleted successfully'
        ]);
    }

    /**
     * Find mysqldump executable
     */
    private function findMysqldump()
    {
        $paths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try system PATH
        exec('where mysqldump 2>&1', $output, $return);
        if ($return === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        exec('which mysqldump 2>&1', $output, $return);
        if ($return === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Create backup using PHP (fallback when mysqldump is not available)
     */
    private function createPhpBackup($backup, $type, $tables, $contentTables, $absolutePath)
    {
        try {
            $startTime = microtime(true);

            // Determine which tables to backup
            $tablesToBackup = [];
            switch ($type) {
                case 'full':
                    $tablesToBackup = $this->getAllTables();
                    break;
                case 'selected_tables':
                    $tablesToBackup = $tables;
                    break;
                case 'user_data':
                    $tablesToBackup = ['users', 'user_profiles', 'user_favorites', 'user_subscriptions'];
                    break;
                case 'content_data':
                    $tablesToBackup = $contentTables;
                    break;
                default:
                    throw new \Exception('Invalid backup type');
            }

            // Create SQL dump
            $sql = "-- MarineNG Database Backup\n";
            $sql .= "-- Generated: " . now()->toDateTimeString() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tablesToBackup as $table) {
                if (!$this->tableExists($table)) {
                    continue;
                }

                // Get table structure
                $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
                $sql .= "-- Table: {$table}\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

                // Get table data
                $rows = DB::table($table)->get();
                if ($rows->count() > 0) {
                    $sql .= "-- Data for table: {$table}\n";
                    foreach ($rows as $row) {
                        $values = array_map(function ($value) {
                            if ($value === null) {
                                return 'NULL';
                            }
                            return "'" . addslashes($value) . "'";
                        }, (array) $row);

                        $sql .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Write to file
            File::put($absolutePath, $sql);

            $duration = round(microtime(true) - $startTime, 2);
            $size = filesize($absolutePath) / 1024 / 1024;

            // Create proper relative path for download
            $relativePath = 'backups/' . basename($absolutePath);

            $backup->update([
                'status' => 'completed',
                'file_path' => $relativePath,
                'size' => round($size, 2),
                'completed_at' => Carbon::now(),
                'duration' => $duration
            ]);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Backup created successfully (PHP method)',
                'data' => $backup
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'completed_at' => Carbon::now(),
                'log_message' => 'PHP backup failed: ' . $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Backup creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all tables in database
     */
    private function getAllTables()
    {
        $database = env('DB_DATABASE');
        $tables = DB::select("SHOW TABLES");
        $key = "Tables_in_{$database}";

        return array_map(function ($table) use ($key) {
            return $table->$key;
        }, $tables);
    }

    /**
     * Check if table exists
     */
    private function tableExists($table)
    {
        try {
            DB::table($table)->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
