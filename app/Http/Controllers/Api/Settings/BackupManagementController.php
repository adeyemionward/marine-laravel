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
        $storageUsed = Backup::sum('size'); // in MB

        $history = Backup::latest()->get();

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total_backups' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'storage_used' => round($storageUsed, 2) . ' MB'
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
            'status' => true,
            'tables' => $tableList
        ]);
    }


    public function createBackup(Request $request)
    {
        $type = $request->input('type', 'full'); // full, incremental, selected_tables, user_data, content_data
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
            'type' => $type,
            'status' => 'in_progress',
            'started_at' => Carbon::now(),
            'created_by' => $request->user()->name ?? 'System Administrator'
        ]);

        $fileName = 'backup_' . $type . '_' . now()->format('Y_m_d_His') . '.sql';
        $storageRelativePath = 'backups/' . $fileName;
        $absolutePath = storage_path('app/' . $storageRelativePath);

        if (!File::isDirectory(storage_path('app/backups'))) {
            File::makeDirectory(storage_path('app/backups'), 0775, true);
        }

        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbName = env('DB_DATABASE');
        $dbHost = env('DB_HOST', '127.0.0.1');

        $mysqldumpPath = 'mysqldump';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $mysqldumpPath = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
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

        // Execute the backup
        $startTime = microtime(true);
        exec($command, $output, $returnVar);
        $duration = round(microtime(true) - $startTime, 2);

        Log::info('Backup Debug', [
            'command' => $command,
            'returnVar' => $returnVar,
            'output' => $output,
            'path' => $absolutePath,
            'file_exists' => File::exists($absolutePath),
        ]);

        if ($returnVar === 0 && File::exists($absolutePath) && File::size($absolutePath) > 0) {
            $size = filesize($absolutePath) / 1024 / 1024;
            $backup->update([
                'status' => 'completed',
                'file_path' => '/storage/' . str_replace(storage_path('app/'), '', $absolutePath),
                'size' => round($size, 2),
                'completed_at' => Carbon::now(),
                'duration' => $duration
            ]);
        } else {
            $backup->update([
                'status' => 'failed',
                'completed_at' => Carbon::now(),
                'duration' => $duration,
                'log_message' => 'Backup command failed or file not created.'
            ]);

            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        return response()->json($backup);
    }


    /**
     * Delete Backup
     */
    public function deleteBackup($id)
    {
        $backup = Backup::findOrFail($id);

        if ($backup->file_path && file_exists(public_path($backup->file_path))) {
            unlink(public_path($backup->file_path));
        }

        $backup->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Backup deleted successfully'
        ]);
    }
}
