<?php
/**
 * Storage Link Creator for GoDaddy Shared Hosting
 *
 * This script creates the necessary symbolic link from public/storage to storage/app/public
 * when php artisan storage:link command is not available
 *
 * Usage: Access this file via browser: https://yourdomain.com/create-storage-link.php
 * Delete this file after successful creation for security
 */

// Security: Only allow execution in non-production or with a secret key
$SECRET_KEY = 'marine_storage_link_2025'; // Change this to something unique
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $SECRET_KEY) {
    die('Access Denied. Provide correct key parameter.');
}

echo "<!DOCTYPE html><html><head><title>Storage Link Creator</title><style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<h1>Storage Link Creator for GoDaddy</h1>";

// Get paths
$target = realpath(__DIR__ . '/../storage/app/public');
$link = __DIR__ . '/storage';

echo "<div class='info'><strong>Configuration:</strong><br>";
echo "Target (storage/app/public): <code>$target</code><br>";
echo "Link (public/storage): <code>$link</code></div>";

// Check if target directory exists
if (!file_exists($target)) {
    echo "<div class='error'><strong>Error:</strong> Target directory does not exist!<br>";
    echo "Expected path: <code>$target</code><br>";
    echo "Please ensure your Laravel installation is complete.</div>";
    echo "</body></html>";
    exit(1);
}

// Check if link already exists
if (file_exists($link) || is_link($link)) {
    if (is_link($link)) {
        $current_target = readlink($link);
        echo "<div class='warning'><strong>Symlink Already Exists!</strong><br>";
        echo "Current link points to: <code>$current_target</code><br>";

        if ($current_target === $target) {
            echo "The link is correctly configured. ✓</div>";
            echo "<div class='info'>You can now safely delete this file.</div>";
        } else {
            echo "The link points to a different location!</div>";
            echo "<div class='warning'>To recreate the link, delete the existing one first:</div>";
            echo "<pre>1. Go to cPanel File Manager\n2. Navigate to public directory\n3. Delete the 'storage' link\n4. Refresh this page</pre>";
        }
    } else {
        echo "<div class='error'><strong>Error:</strong> A file or directory named 'storage' already exists in public!<br>";
        echo "Please rename or delete it manually before creating the symlink.</div>";
    }
    echo "</body></html>";
    exit(0);
}

// Try to create the symbolic link
try {
    // Method 1: Using symlink() function
    if (function_exists('symlink')) {
        if (@symlink($target, $link)) {
            echo "<div class='success'><strong>✓ Success!</strong> Symbolic link created successfully.</div>";

            // Verify the link
            if (is_link($link)) {
                $link_target = readlink($link);
                echo "<div class='success'>Verification: Link points to <code>$link_target</code></div>";

                // Test by creating a test file
                $test_file = $target . '/test_' . time() . '.txt';
                file_put_contents($test_file, 'Test file to verify storage link');

                $test_url = '/storage/test_' . basename($test_file);
                echo "<div class='info'><strong>Test:</strong> ";
                echo "<a href='$test_url' target='_blank'>Click here to test storage access</a>";
                echo "<br><small>If you can access this file, your storage link is working correctly!</small></div>";

                echo "<div class='warning'><strong>Important:</strong> ";
                echo "<ul>";
                echo "<li>Delete this file (<code>create-storage-link.php</code>) for security</li>";
                echo "<li>Make sure your .env file has the correct APP_URL</li>";
                echo "<li>Clear Laravel cache: <code>php artisan config:clear</code></li>";
                echo "</ul></div>";
            } else {
                echo "<div class='error'>Verification failed: Created path is not a symbolic link!</div>";
            }
        } else {
            throw new Exception('symlink() function failed. Error: ' . error_get_last()['message']);
        }
    } else {
        throw new Exception('symlink() function is not available on this server');
    }
} catch (Exception $e) {
    echo "<div class='error'><strong>Failed to create symbolic link!</strong><br>";
    echo "Error: " . $e->getMessage() . "</div>";

    echo "<div class='warning'><strong>Alternative Solution:</strong><br>";
    echo "Since automatic symlink creation failed, please create it manually:<br><br>";
    echo "<strong>Option 1: Via SSH (if available)</strong><br>";
    echo "<pre>cd " . __DIR__ . "\nln -s $target storage</pre><br>";

    echo "<strong>Option 2: Via cPanel File Manager</strong><br>";
    echo "<ol>";
    echo "<li>Log into your cPanel</li>";
    echo "<li>Open File Manager</li>";
    echo "<li>Navigate to your <code>public_html</code> (or <code>public</code>) directory</li>";
    echo "<li>Right-click → Create Symbolic Link</li>";
    echo "<li>Link Name: <code>storage</code></li>";
    echo "<li>Target: <code>../storage/app/public</code></li>";
    echo "</ol><br>";

    echo "<strong>Option 3: Contact GoDaddy Support</strong><br>";
    echo "Ask them to enable the <code>symlink()</code> function or create the link for you.</div>";
}

echo "<div class='info'><strong>What is a Storage Link?</strong><br>";
echo "Laravel stores uploaded files in <code>storage/app/public</code> for security.<br>";
echo "A symbolic link allows these files to be accessible via <code>public/storage</code>.<br>";
echo "This is necessary for banner images to display correctly on your website.</div>";

echo "</body></html>";
?>
