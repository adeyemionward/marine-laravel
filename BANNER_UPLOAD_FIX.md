# Banner Upload CSRF Fix - Deployment Guide

## Problem
Banner image uploads were failing with "CSRF token mismatch" error when uploading through the admin dashboard.

Error details:
```
Failed to load resource: the server responded with a status of 500 (Internal Server Error)
Error: Failed to upload banner media: CSRF token mismatch.
```

## Root Cause
1. The `/api/v1/cloudinary/upload` endpoint was unprotected (no authentication)
2. Sanctum's stateful middleware was checking for CSRF tokens on POST requests
3. File upload requests weren't sending CSRF tokens

## Solutions Implemented

### 1. Added Authentication to Upload Routes
**File**: `routes/api.php` (lines 304-324)

```php
// File upload management (using Laravel native storage) - requires authentication
Route::middleware('auth:sanctum')->prefix('uploads')->group(function () {
    Route::post('/image', [FileUploadController::class, 'uploadImage']);
    // ... other routes
});

// Legacy Cloudinary routes (for backward compatibility) - requires authentication
Route::middleware('auth:sanctum')->prefix('cloudinary')->group(function () {
    Route::post('/upload', [FileUploadController::class, 'uploadImage']);
    // ... other routes
});
```

### 2. Excluded Upload Routes from CSRF Verification
**File**: `bootstrap/app.php` (lines 19-23)

```php
// Exclude file upload routes from CSRF verification
$middleware->validateCsrfTokens(except: [
    'api/v1/cloudinary/*',
    'api/v1/uploads/*',
]);
```

This allows file uploads to bypass CSRF checks since they're already protected by Bearer token authentication.

### 3. Ensured Storage Directory Exists
Banner uploads are saved to: `public/uploads/banners/`

## Deployment Steps

### On Production Server:

1. **Pull latest code**:
   ```bash
   cd /path/to/marine-laravel
   git pull origin main
   ```

2. **Ensure storage directories exist**:
   ```bash
   # Create uploads directories
   mkdir -p public/uploads/banners
   mkdir -p public/uploads/equipment
   mkdir -p public/uploads/profiles
   mkdir -p public/uploads/documents

   # Set proper permissions
   chmod -R 775 public/uploads
   chown -R www-data:www-data public/uploads

   # Or if using a different user:
   chown -R your-web-user:your-web-group public/uploads
   ```

3. **Create storage symlink** (if not already done):
   ```bash
   php artisan storage:link
   ```

4. **Clear all caches**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

5. **Rebuild optimized caches**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

6. **Restart PHP-FPM**:
   ```bash
   sudo systemctl restart php8.2-fpm
   ```

7. **Restart Web Server**:
   ```bash
   sudo systemctl restart nginx
   # OR
   sudo systemctl restart apache2
   ```

## Verification

### 1. Check Authentication
Ensure you're logged in as admin:
```bash
# Test auth endpoint
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.marine.ng/api/v1/admin/test
```

### 2. Test Banner Upload
From admin dashboard:
1. Go to Banner Management
2. Click "Upload Banner"
3. Select an image file
4. Upload should succeed

### 3. Check File Permissions
```bash
# On server
ls -la public/uploads/banners/
# Should show: drwxrwxr-x www-data www-data
```

### 4. Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

Look for:
- ✅ `FileUploadController::uploadImage START`
- ✅ `FileUploadController: fileStorageService->uploadImage completed`
- ❌ NO CSRF token errors

## Frontend Configuration

No frontend changes needed. The frontend should:
1. Send authenticated requests (with Bearer token)
2. Upload to `/api/v1/cloudinary/upload`
3. Pass `folder: 'banners'` in the request

## Troubleshooting

### Still Getting CSRF Error?

1. **Clear ALL caches**:
   ```bash
   php artisan optimize:clear
   ```

2. **Verify routes are registered**:
   ```bash
   php artisan route:list | grep cloudinary
   ```

Should show:
```
POST    api/v1/cloudinary/upload ......... auth:sanctum
```

3. **Check middleware stack**:
   ```bash
   php artisan route:list --path=cloudinary/upload --verbose
   ```

4. **Verify authentication**:
   - Check browser DevTools > Network tab
   - Look for `Authorization: Bearer ...` header
   - If missing, user needs to login again

### Upload Fails with 500 Error?

1. **Check storage permissions**:
   ```bash
   ls -la public/uploads/
   ```

2. **Check disk space**:
   ```bash
   df -h
   ```

3. **Check PHP upload limits** in `php.ini`:
   ```ini
   upload_max_filesize = 20M
   post_max_size = 20M
   memory_limit = 256M
   max_execution_time = 300
   ```

4. **Restart PHP-FPM after php.ini changes**:
   ```bash
   sudo systemctl restart php8.2-fpm
   ```

### Images Not Displaying?

1. **Check symlink**:
   ```bash
   ls -la public/storage
   # Should point to: ../storage/app/public
   ```

2. **Verify URL format**:
   - Should be: `https://api.marine.ng/uploads/banners/filename.png`
   - NOT: `http://...` (mixed content error)

3. **Check HTTPS configuration** (see `HTTPS_FIX_DEPLOYMENT.md`)

## Security Notes

✅ Routes now require authentication (`auth:sanctum`)
✅ Only authenticated users can upload files
✅ CSRF bypass is safe because Bearer tokens are used
✅ Files are validated (image type, size limits)
⚠️ Ensure only admins can access upload endpoints
⚠️ Consider adding file type whitelist in FileStorageService

## File Structure

After upload, files are stored as:
```
public/
  uploads/
    banners/
      original_filename_timestamp_randomid.ext
    equipment/
    profiles/
    documents/
```

URLs accessible at:
```
https://api.marine.ng/uploads/banners/filename.png
```

## Related Files

- `routes/api.php` - Route definitions
- `bootstrap/app.php` - Middleware configuration
- `app/Http/Controllers/Api/FileUploadController.php` - Upload handler
- `app/Services/FileStorageService.php` - File storage logic
- `config/sanctum.php` - Authentication config

## Contact

If issues persist:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server logs: `/var/log/nginx/error.log` or `/var/log/apache2/error.log`
3. Check PHP-FPM logs: `/var/log/php8.2-fpm.log`
