# Fix Mixed Content HTTPS Issues - Deployment Guide

## Problem
The application was serving HTTP URLs when accessed via HTTPS, causing browsers to block images and other resources due to mixed content security policy.

## Solution Implemented

### 1. AppServiceProvider - Force HTTPS URLs
**File**: `app/Providers/AppServiceProvider.php`

Added code to force HTTPS URL generation when:
- App is in production environment
- Request comes through an HTTPS proxy (detected via `X-Forwarded-Proto` header)

```php
public function boot(): void
{
    // Force HTTPS URLs in production
    if ($this->app->environment('production') || request()->header('X-Forwarded-Proto') === 'https') {
        URL::forceScheme('https');
    }
}
```

### 2. TrustProxies Middleware
**Files**:
- `app/Http/Middleware/TrustProxies.php` (created)
- `bootstrap/app.php` (updated)

Created middleware to trust proxy headers from load balancers/reverse proxies.

### 3. Environment Configuration
**File**: `.env.production.example`

Created production environment template with HTTPS settings.

## Deployment Steps

### On Production Server (13.57.153.110):

1. **Update .env file**:
   ```bash
   cd /path/to/marine-laravel
   nano .env
   ```

2. **Set these critical values**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://marine.ng
   ASSET_URL=https://marine.ng
   FRONTEND_URL=https://marine.ng

   SANCTUM_STATEFUL_DOMAINS=marine.ng,www.marine.ng
   CORS_ALLOWED_ORIGINS=https://marine.ng,https://www.marine.ng
   ```

3. **Clear application cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

4. **Rebuild optimized cache**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. **Restart PHP-FPM** (if using):
   ```bash
   sudo systemctl restart php8.2-fpm
   # OR
   sudo service php8.2-fpm restart
   ```

6. **Restart Web Server**:
   ```bash
   # For Nginx:
   sudo systemctl restart nginx

   # For Apache:
   sudo systemctl restart apache2
   ```

## Nginx Configuration (if using)

Ensure your Nginx config passes the correct proxy headers:

```nginx
server {
    listen 80;
    server_name marine.ng www.marine.ng;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name marine.ng www.marine.ng;

    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    root /path/to/marine-laravel/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # IMPORTANT: Pass proxy headers for HTTPS detection
        fastcgi_param HTTPS on;
        fastcgi_param HTTP_X_FORWARDED_PROTO https;
        fastcgi_param HTTP_X_FORWARDED_PORT 443;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## Apache Configuration (if using)

Add to your `.htaccess` or VirtualHost configuration:

```apache
<IfModule mod_headers.c>
    # Force HTTPS
    Header always set X-Forwarded-Proto "https"
    Header always set X-Forwarded-Port "443"
</IfModule>

# Redirect HTTP to HTTPS
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
</IfModule>
```

## Verification

After deployment, verify HTTPS is working:

1. **Check API responses**:
   ```bash
   curl -I https://13.57.153.110/api/v1/sellers
   ```

   Look for `Location` or URL fields in the response - they should start with `https://`

2. **Test banner image URL**:
   ```bash
   curl https://13.57.153.110/api/v1/banners | jq '.data[0].image_url'
   ```

   Should return: `https://13.57.153.110/uploads/banners/...`

3. **Check browser console**:
   - Open https://marine.ng/admin-dashboard
   - Open DevTools Console (F12)
   - Should see NO mixed content errors

4. **Test asset URLs**:
   ```bash
   php artisan tinker
   >>> asset('uploads/banners/test.png')
   ```

   Should output: `https://13.57.153.110/uploads/banners/test.png`

## Troubleshooting

### Still seeing HTTP URLs?

1. **Clear ALL caches**:
   ```bash
   php artisan optimize:clear
   ```

2. **Check APP_ENV**:
   ```bash
   php artisan config:show app.env
   ```
   Should show: `production`

3. **Verify proxy headers** are being passed:
   ```bash
   # In PHP
   php artisan tinker
   >>> request()->header('X-Forwarded-Proto')
   ```
   Should return: `https`

4. **Check Laravel logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Images still blocked?

1. Verify SSL certificate is valid
2. Check CORS configuration
3. Ensure storage symlink exists:
   ```bash
   php artisan storage:link
   ```

## Frontend Update

No frontend changes needed. The frontend will automatically use the HTTPS URLs returned by the backend API.

## Security Notes

- ✅ All URLs now generated with HTTPS
- ✅ Proxy headers trusted for load balancer detection
- ✅ Mixed content issues resolved
- ✅ Secure cookie transmission enabled
- ⚠️ Ensure SSL certificate is valid and up-to-date
- ⚠️ Keep APP_DEBUG=false in production
- ⚠️ Use strong APP_KEY (generate with `php artisan key:generate`)

## Contact

If issues persist after deployment, check:
1. Server error logs: `/var/log/nginx/error.log` or `/var/log/apache2/error.log`
2. Laravel logs: `storage/logs/laravel.log`
3. PHP-FPM logs: `/var/log/php8.2-fpm.log`
