# GoDaddy Deployment Fix for Banner Image Upload

## Problem
Banner images upload successfully but don't display. The URL is saved in the database but images show "Failed to load media" in the preview and don't appear on the hero UI carousel.

## Root Cause
1. **Missing Storage Symlink**: Laravel needs a symbolic link from `public/storage` to `storage/app/public`
2. **Incorrect URL Format**: URLs might be relative instead of absolute
3. **File Permissions**: GoDaddy might have restrictive file permissions

## Solutions

### Solution 1: Create Storage Symlink (REQUIRED)

On GoDaddy shared hosting, you cannot use `php artisan storage:link`. Instead, create the symlink manually:

#### Option A: Via cPanel File Manager
1. Log into cPanel
2. Go to File Manager
3. Navigate to your Laravel `public` directory
4. Check if `storage` folder/link exists
5. If not, create a symbolic link:
   - Right-click → Create Symbolic Link
   - Link Path: `storage`
   - Target: `../storage/app/public`

#### Option B: Via SSH (if available)
```bash
cd /path/to/your/laravel/public
ln -s ../storage/app/public storage
```

#### Option C: Via PHP Script (if SSH not available)
Create a file `create-storage-link.php` in your Laravel root:

```php
<?php
$target = $_SERVER['DOCUMENT_ROOT'] . '/../storage/app/public';
$link = $_SERVER['DOCUMENT_ROOT'] . '/storage';

if (file_exists($link)) {
    echo "Storage link already exists!\n";
} else {
    if (symlink($target, $link)) {
        echo "Storage link created successfully!\n";
    } else {
        echo "Failed to create storage link. Try manual creation.\n";
    }
}
```

Access: `https://yourdomain.com/create-storage-link.php`

### Solution 2: Verify File Permissions

Set correct permissions on GoDaddy:

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public/storage
```

Or via cPanel File Manager:
- Right-click folder → Change Permissions
- Set to `755` for directories
- Set to `644` for files

### Solution 3: Ensure .env Configuration

Make sure your `.env` file has the correct `APP_URL`:

```env
APP_URL=https://your-actual-domain.com
```

**Important**: Do NOT use `http://localhost` or `http://127.0.0.1` on production!

### Solution 4: Clear Laravel Caches

After making changes, clear all caches:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Solution 5: Test File Upload

Create a test script `test-upload.php` in public folder:

```php
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test storage URL generation
$testPath = 'images/banners/test.jpg';
$url = Storage::disk('public')->url($testPath);

echo "Storage Path: $testPath\n";
echo "Generated URL: $url\n";
echo "Full Path: " . Storage::disk('public')->path($testPath) . "\n";
echo "Storage exists: " . (file_exists(public_path('storage')) ? 'YES' : 'NO') . "\n";
echo "Is symlink: " . (is_link(public_path('storage')) ? 'YES' : 'NO') . "\n";
```

Access: `https://yourdomain.com/test-upload.php`

## Code Changes Made

The following files have been updated to ensure absolute URLs:

### 1. FileStorageService.php
- Updated `uploadImage()` to generate absolute URLs
- Updated `getOptimizedUrl()` to return absolute URLs
- Ensures URLs work correctly on GoDaddy shared hosting

### 2. Backend Changes Summary
- ✅ File uploads save to `storage/app/public/images/banners/`
- ✅ URLs are now absolute (include full domain)
- ✅ URLs format: `https://yourdomain.com/storage/images/banners/filename.jpg`

## Verification Steps

After deployment:

1. **Check Storage Link**:
   ```
   https://yourdomain.com/storage
   ```
   Should show directory listing or 403 (not 404)

2. **Upload Test**:
   - Go to Banner Management
   - Edit a banner
   - Upload a new image
   - Check browser console for the returned URL
   - Verify URL starts with `https://yourdomain.com/storage/...`

3. **Database Check**:
   - Look at the `media_url` field in the `banners` table
   - Should contain full absolute URL

4. **Direct Access**:
   - Copy a banner's `media_url` from database
   - Paste in browser address bar
   - Image should load directly

## Troubleshooting

### Image Still Not Loading?

1. **Check Storage Link**:
   ```bash
   ls -la public/
   ```
   Should show: `storage -> ../storage/app/public`

2. **Check File Actually Uploaded**:
   ```bash
   ls -la storage/app/public/images/banners/
   ```
   Should show your uploaded files

3. **Check .htaccess**:
   Ensure `public/.htaccess` has:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule ^ index.php [L]
   </IfModule>
   ```

4. **Check Console Errors**:
   - Open browser DevTools (F12)
   - Go to Network tab
   - Try loading the image
   - Check for 404 or 403 errors

### Still Having Issues?

Contact GoDaddy support and ask them to:
1. Enable `symlink()` function for your account
2. Check if `open_basedir` restriction is blocking storage access
3. Verify mod_rewrite is enabled

## Alternative: Direct Public Upload (If Symlink Fails)

If symlinks absolutely don't work on GoDaddy, modify FileStorageService to save directly to public:

```php
// Change storage path to save directly in public
$storagePath = "uploads/banners/{$filename}";
$path = $file->storeAs("uploads/banners", $filename, 'public_direct');

// And in config/filesystems.php, add:
'public_direct' => [
    'driver' => 'local',
    'root' => public_path('uploads'),
    'url' => env('APP_URL').'/uploads',
    'visibility' => 'public',
],
```

**Note**: This is less secure but works if symlinks are disabled.

## Success Indicators

✅ Image preview shows in banner edit modal
✅ Banner appears in hero carousel on frontend
✅ Direct URL access loads the image
✅ No "Failed to load media" errors

## Need Help?

If issues persist after following all steps, check:
1. Laravel logs: `storage/logs/laravel.log`
2. PHP error logs in cPanel
3. Browser console (F12) for JavaScript errors
