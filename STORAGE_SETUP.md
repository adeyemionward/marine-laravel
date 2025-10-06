# Laravel Native File Storage Setup Guide

## Overview

This application uses **Laravel's native file storage system** instead of Cloudinary. All uploaded images (banners, equipment listings, profiles, documents) are stored locally in the `storage/app/public` directory and served via a symbolic link to `public/storage`.

---

## Storage Structure

```
marine-laravel/
├── storage/
│   └── app/
│       └── public/
│           └── images/
│               ├── banners/         # Banner ad images
│               ├── equipment/       # Equipment listing images
│               ├── profiles/        # User profile images
│               ├── documents/       # PDF/document uploads
│               └── general/         # Other uploads
└── public/
    └── storage/  → ../storage/app/public (symbolic link)
```

---

## Initial Setup

### 1. Create Storage Symbolic Link

Run this command to create a symbolic link from `public/storage` to `storage/app/public`:

```bash
cd marine-laravel
php artisan storage:link
```

**Output:**
```
The [public/storage] link has been connected to [storage/app/public]
```

If you see "The link already exists", the setup is complete!

### 2. Set Proper Permissions (Linux/Mac)

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### 3. Verify .gitignore

Ensure `storage/app/public` is properly ignored in `.gitignore`:

```
/storage/*.key
/storage/app/*
!/storage/app/.gitignore
!/storage/app/public
/storage/app/public/*
!/storage/app/public/.gitignore
```

---

## How It Works

### Backend (Laravel)

**FileUploadController** (`app/Http/Controllers/Api/FileUploadController.php`)
- Handles all image uploads via `/api/v1/uploads/*` endpoints
- Uses `FileStorageService` for actual file operations

**FileStorageService** (`app/Services/FileStorageService.php`)
- Stores files using Laravel's `Storage::disk('public')`
- Generates unique filenames: `{slug}_{timestamp}_{random}.{ext}`
- Returns URLs like: `/storage/images/banners/banner_1234567890_abc123.jpg`

**Routes:**
```php
// New preferred routes
POST /api/v1/uploads/image              // Single upload
POST /api/v1/uploads/images             // Multiple upload
DELETE /api/v1/uploads/image            // Delete single
DELETE /api/v1/uploads/images           // Delete multiple

// Legacy routes (backward compatibility)
POST /api/v1/cloudinary/upload          // Still works!
POST /api/v1/cloudinary/upload-multiple
```

### Frontend (React)

**API Client** (`src/lib/api.js`)
```javascript
// New preferred way
api.uploads.uploadImage(file, 'banners')

// Legacy way (still works)
api.cloudinary.uploadImage(file, 'banners')
```

**Example Usage:**
```javascript
const handleBannerUpload = async (file) => {
  try {
    const result = await api.uploads.uploadImage(file, 'banners');

    if (result.success) {
      console.log('Uploaded URL:', result.data.url);
      // URL will be: /storage/images/banners/filename.jpg
    }
  } catch (error) {
    console.error('Upload failed:', error);
  }
};
```

---

## File Upload Limits

**PHP Configuration** (automatically set by FileUploadController):
- `upload_max_filesize`: 20MB
- `post_max_size`: 20MB
- `memory_limit`: 256MB
- `max_execution_time`: 300 seconds

**Validation Rules:**
- Single image: Max 10MB (`max:10240`)
- Allowed types: `image/*` (jpg, png, gif, webp, etc.)
- Folders: `equipment`, `profiles`, `documents`, `banners`

---

## URL Structure

### Storage Path
```
storage/app/public/images/banners/banner_1696234567_xY9zKl2m.jpg
```

### Public URL
```
http://yourdomain.com/storage/images/banners/banner_1696234567_xY9zKl2m.jpg
```

### In Database
Store just the relative path:
```
images/banners/banner_1696234567_xY9zKl2m.jpg
```

Then display using:
```php
Storage::url($relativePath)
// or
asset('storage/' . $relativePath)
```

---

## Troubleshooting

### Issue 1: Images not loading (404 errors)

**Symptoms:** Uploaded images show 404 error

**Solution:**
```bash
# Recreate symbolic link
cd marine-laravel
rm public/storage
php artisan storage:link
```

### Issue 2: Permission denied errors

**Solution:**
```bash
# Fix permissions (Linux/Mac)
sudo chmod -R 775 storage
sudo chown -R www-data:www-data storage

# For development (Windows)
# No action needed - Windows doesn't have the same permission issues
```

### Issue 3: Upload fails with "413 Request Entity Too Large"

**Solution:** Increase Nginx upload limit in `/etc/nginx/nginx.conf`:
```nginx
client_max_body_size 20M;
```

### Issue 4: Storage link doesn't work on Windows

**Solution:** Run CMD as Administrator and use mklink:
```cmd
cd C:\path\to\marine-laravel\public
mklink /D storage ..\storage\app\public
```

---

## Production Deployment

### Option 1: Keep Files on Application Server

1. Run `php artisan storage:link` on production server
2. Ensure proper permissions are set
3. Files stored locally in `/storage/app/public`

**Pros:**
- Simple setup
- No external dependencies

**Cons:**
- Not suitable for multi-server deployments
- Backup complexity

### Option 2: Use Cloud Storage (S3, DO Spaces, etc.)

**Update config/filesystems.php:**
```php
'default' => env('FILESYSTEM_DISK', 's3'),

'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
],
```

**Update .env:**
```
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=marine-storage
```

**No code changes needed!** FileStorageService will automatically use the configured disk.

---

## Migration from Cloudinary

If you were previously using Cloudinary:

1. **Backend:** Already migrated! FileUploadController uses Laravel storage
2. **Frontend:** Legacy `api.cloudinary.*` methods still work
3. **Gradual Migration:** Update components to use `api.uploads.*` when convenient
4. **Database:** Old Cloudinary URLs remain valid, new uploads use `/storage/*` URLs

---

## Backup Strategy

### Manual Backup
```bash
# Backup entire storage directory
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

### Automated Backup (Laravel Backup Package)
```bash
composer require spatie/laravel-backup
php artisan backup:run
```

---

## API Reference

### Upload Single Image
```http
POST /api/v1/uploads/image
Content-Type: multipart/form-data

image: (binary file)
folder: "banners" | "equipment" | "profiles" | "documents"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "public_id": "images/banners/banner_1696234567_xY9zKl2m.jpg",
    "path": "images/banners/banner_1696234567_xY9zKl2m.jpg",
    "url": "/storage/images/banners/banner_1696234567_xY9zKl2m.jpg",
    "width": 1920,
    "height": 1080,
    "format": "jpg",
    "bytes": 245678,
    "created_at": "2025-10-04T12:00:00Z"
  }
}
```

### Delete Image
```http
DELETE /api/v1/uploads/image
Content-Type: application/json

{
  "public_id": "images/banners/banner_1696234567_xY9zKl2m.jpg"
}
```

---

## Performance Optimization

### 1. Image Optimization (Optional)

Install Intervention Image for automatic optimization:
```bash
composer require intervention/image
```

**Update FileStorageService to resize/compress images automatically**

### 2. CDN Integration

Serve static files through a CDN:
```
https://cdn.yourdomain.com/storage/images/banners/...
```

Update `APP_URL` or use asset helpers with CDN URL.

### 3. Lazy Loading

Use lazy loading in React:
```jsx
<img
  src={imageUrl}
  loading="lazy"
  alt="Banner"
/>
```

---

## Summary

✅ **Native Laravel storage** - No external dependencies
✅ **Symbolic link** - `public/storage` → `storage/app/public`
✅ **FileUploadController** - Renamed from CloudinaryController
✅ **Backward compatible** - Legacy `/cloudinary/*` routes still work
✅ **Flexible** - Easy to switch to S3/Spaces later

**No Cloudinary needed!** Everything runs on Laravel's built-in storage system.
