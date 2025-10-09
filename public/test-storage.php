<?php
/**
 * Storage Diagnostic Tool for GoDaddy
 *
 * This script helps diagnose storage and file upload issues
 *
 * Usage: https://yourdomain.com/test-storage.php?key=marine_storage_test_2025
 * Delete this file after diagnostics for security
 */

// Security check
$SECRET_KEY = 'marine_storage_test_2025';
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $SECRET_KEY) {
    die('Access Denied. Provide correct key parameter.');
}

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Storage Diagnostic Tool</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007bff;
            color: white;
        }
        .status-ok {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .status-fail {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 4px;
        }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 25px; }
    </style>
</head>
<body>

<h1>🔍 Storage Diagnostic Tool</h1>

<?php
// 1. Laravel Configuration
echo "<div class='card'>";
echo "<h2>1. Laravel Configuration</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$app_url = Config::get('app.url');
$app_env = Config::get('app.env');
$filesystem_disk = Config::get('filesystems.default');

echo "<tr><td>APP_URL</td><td><code>$app_url</code></td><td>";
echo (filter_var($app_url, FILTER_VALIDATE_URL) && !str_contains($app_url, 'localhost'))
    ? "<span class='status-ok'>✓ OK</span>"
    : "<span class='status-fail'>⚠ Check .env file</span>";
echo "</td></tr>";

echo "<tr><td>APP_ENV</td><td><code>$app_env</code></td><td>";
echo $app_env === 'production' ? "<span class='status-ok'>Production</span>" : "<span class='warning'>Development</span>";
echo "</td></tr>";

echo "<tr><td>Default Filesystem</td><td><code>$filesystem_disk</code></td><td>";
echo "<span class='status-ok'>Configured</span>";
echo "</td></tr>";

echo "</table>";
echo "</div>";

// 2. Storage Paths
echo "<div class='card'>";
echo "<h2>2. Storage Paths & Permissions</h2>";

$paths_to_check = [
    'Public Directory' => public_path(),
    'Storage Directory' => storage_path(),
    'Public Storage Link' => public_path('storage'),
    'Storage App Public' => storage_path('app/public'),
    'Banner Storage' => storage_path('app/public/images/banners'),
];

echo "<table>";
echo "<tr><th>Path</th><th>Location</th><th>Exists</th><th>Writable</th><th>Type</th></tr>";

foreach ($paths_to_check as $name => $path) {
    $exists = file_exists($path);
    $writable = is_writable($path);
    $type = is_link($path) ? 'Symlink' : (is_dir($path) ? 'Directory' : 'File');

    echo "<tr>";
    echo "<td><strong>$name</strong></td>";
    echo "<td><code>" . str_replace($_SERVER['DOCUMENT_ROOT'], '[ROOT]', $path) . "</code></td>";
    echo "<td>" . ($exists ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td>";
    echo "<td>" . ($writable ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td>";
    echo "<td>$type</td>";
    echo "</tr>";

    if (is_link($path)) {
        $target = readlink($path);
        echo "<tr><td colspan='5' style='padding-left: 40px; font-size: 0.9em; color: #666;'>";
        echo "→ Points to: <code>$target</code>";
        echo "</td></tr>";
    }
}

echo "</table>";
echo "</div>";

// 3. Test File Upload & URL Generation
echo "<div class='card'>";
echo "<h2>3. File Upload Test</h2>";

try {
    // Create test file
    $test_filename = 'test_banner_' . time() . '.txt';
    $test_content = 'Marine Banner Test - ' . date('Y-m-d H:i:s');
    $test_path = 'images/banners/' . $test_filename;

    // Store using Laravel Storage
    Storage::disk('public')->put($test_path, $test_content);

    echo "<p class='success'>✓ Test file created successfully</p>";
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";

    // Get URL
    $url = Storage::disk('public')->url($test_path);
    echo "<tr><td>Storage Path</td><td><code>$test_path</code></td></tr>";
    echo "<tr><td>Generated URL</td><td><code>$url</code></td></tr>";

    // Check if URL is absolute
    $is_absolute = parse_url($url, PHP_URL_SCHEME) !== null;
    echo "<tr><td>URL Type</td><td>";
    echo $is_absolute ? "<span class='success'>Absolute ✓</span>" : "<span class='error'>Relative ⚠</span>";
    echo "</td></tr>";

    // Get full file system path
    $full_path = Storage::disk('public')->path($test_path);
    echo "<tr><td>File System Path</td><td><code>$full_path</code></td></tr>";

    // Check if file exists
    $file_exists = Storage::disk('public')->exists($test_path);
    echo "<tr><td>File Exists</td><td>";
    echo $file_exists ? "<span class='success'>Yes ✓</span>" : "<span class='error'>No ✗</span>";
    echo "</td></tr>";

    // Get file size
    if ($file_exists) {
        $size = Storage::disk('public')->size($test_path);
        echo "<tr><td>File Size</td><td>$size bytes</td></tr>";
    }

    echo "</table>";

    // Test access
    echo "<h3>Access Test</h3>";
    echo "<p>Try accessing the test file: ";
    echo "<a href='$url' target='_blank' class='info'><strong>Click Here</strong></a></p>";
    echo "<p><small>If you can view the test file, your storage is configured correctly!</small></p>";

    // Cleanup
    Storage::disk('public')->delete($test_path);
    echo "<p class='info'>Test file automatically deleted.</p>";

} catch (Exception $e) {
    echo "<p class='error'>✗ Error during file upload test:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

echo "</div>";

// 4. Recent Banner Uploads
echo "<div class='card'>";
echo "<h2>4. Recent Banner Uploads</h2>";

try {
    $banner_files = Storage::disk('public')->files('images/banners');

    if (empty($banner_files)) {
        echo "<p class='warning'>No banner files found in storage.</p>";
    } else {
        echo "<p>Found " . count($banner_files) . " banner file(s):</p>";
        echo "<table>";
        echo "<tr><th>Filename</th><th>Size</th><th>URL</th><th>Accessible</th></tr>";

        foreach (array_slice($banner_files, -10) as $file) {
            $filename = basename($file);
            $size = Storage::disk('public')->size($file);
            $url = Storage::disk('public')->url($file);

            echo "<tr>";
            echo "<td><code>$filename</code></td>";
            echo "<td>" . number_format($size / 1024, 2) . " KB</td>";
            echo "<td><a href='$url' target='_blank' class='info'>View</a></td>";
            echo "<td>";

            // Try to check if file is accessible
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            echo $http_code == 200 ? "<span class='success'>✓ Yes</span>" : "<span class='error'>✗ No ($http_code)</span>";
            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error listing files: " . $e->getMessage() . "</p>";
}

echo "</div>";

// 5. Recommendations
echo "<div class='card'>";
echo "<h2>5. Recommendations</h2>";

$issues = [];
$recommendations = [];

// Check storage link
if (!is_link(public_path('storage'))) {
    $issues[] = "Storage symbolic link is missing";
    $recommendations[] = "Create storage link: <a href='/create-storage-link.php?key=marine_storage_link_2025'>Click here</a>";
}

// Check APP_URL
if (str_contains($app_url, 'localhost') || str_contains($app_url, '127.0.0.1')) {
    $issues[] = "APP_URL is set to localhost";
    $recommendations[] = "Update APP_URL in .env to your actual domain";
}

// Check banner directory writable
$banner_path = storage_path('app/public/images/banners');
if (!is_writable($banner_path)) {
    $issues[] = "Banner storage directory is not writable";
    $recommendations[] = "Set permissions: chmod -R 755 storage";
}

if (empty($issues)) {
    echo "<p class='success'>✓ No issues detected! Your storage configuration looks good.</p>";
} else {
    echo "<h3 class='error'>Issues Found:</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li class='error'>$issue</li>";
    }
    echo "</ul>";

    echo "<h3>Recommended Actions:</h3>";
    echo "<ol>";
    foreach ($recommendations as $rec) {
        echo "<li>$rec</li>";
    }
    echo "</ol>";
}

echo "</div>";

// 6. Server Information
echo "<div class='card'>";
echo "<h2>6. Server Information</h2>";
echo "<table>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>Laravel Version</td><td>" . app()->version() . "</td></tr>";
echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
echo "<tr><td>Document Root</td><td><code>" . $_SERVER['DOCUMENT_ROOT'] . "</code></td></tr>";
echo "<tr><td>Symlink Function</td><td>" . (function_exists('symlink') ? "<span class='success'>Available</span>" : "<span class='error'>Not Available</span>") . "</td></tr>";
echo "</table>";
echo "</div>";

?>

<div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
    <h3 style="color: #856404;">⚠️ Security Notice</h3>
    <p><strong>Important:</strong> Delete this file (<code>test-storage.php</code>) after completing your diagnostics for security reasons.</p>
</div>

</body>
</html>
