# Gmail Email Configuration Setup

## Current Status
The Gmail configuration has been properly set up with the correct SMTP settings:
- **SMTP Host**: smtp.gmail.com
- **SMTP Port**: 587
- **Encryption**: TLS
- **Enable SMTP**: Yes

## Setting Up Gmail for SMTP

### Step 1: Enable 2-Factor Authentication
1. Go to your Google Account: https://myaccount.google.com/
2. Click on "Security" in the left sidebar
3. Under "How you sign in to Google", enable "2-Step Verification"

### Step 2: Generate App Password
1. After enabling 2FA, go to: https://myaccount.google.com/apppasswords
2. Select "Mail" from the dropdown
3. Select "Other (Custom name)" and enter "Marine.ng"
4. Click "Generate"
5. Copy the 16-character password (spaces don't matter)

### Step 3: Update Gmail Configuration via API

#### Option A: Update existing configuration
```bash
curl -X PUT "http://127.0.0.1:8001/api/v1/admin/communication/email-configs/update/gmail" \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_TOKEN_HERE" \
-d '{
    "username": "your-gmail@gmail.com",
    "password": "your-16-char-app-password",
    "from_email": "noreply@marine.ng"
}'
```

#### Option B: Create new configuration
```bash
curl -X POST "http://127.0.0.1:8001/api/v1/admin/communication/email-configs/store" \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_TOKEN_HERE" \
-d '{
    "driver": "gmail",
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    "username": "your-gmail@gmail.com",
    "password": "your-16-char-app-password",
    "from_email": "noreply@marine.ng",
    "from_name": "Marine.ng System",
    "encryption": "tls",
    "enable_smtp": true
}'
```

### Step 4: Test Email Configuration
```bash
curl -X POST "http://127.0.0.1:8001/api/v1/admin/communication/email-configs/test/gmail" \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_TOKEN_HERE" \
-d '{
    "test_email": "recipient@example.com"
}'
```

## Troubleshooting

### Error: "Authentication unsuccessful"
- **Cause**: Using regular Gmail password instead of App Password
- **Solution**: Generate and use an App Password (see Step 2)

### Error: "SMTP connect() failed"
- **Cause**: Incorrect SMTP settings
- **Solution**: Verify SMTP host is `smtp.gmail.com` and port is `587`

### Error: "Less secure app access"
- **Cause**: Old Gmail security setting
- **Solution**: Use App Passwords instead (Google disabled less secure apps in May 2022)

### Verifying Current Configuration
Check current Gmail settings:
```bash
curl -X GET "http://127.0.0.1:8001/api/v1/admin/communication/email-configs/show/gmail" \
-H "Authorization: Bearer YOUR_TOKEN_HERE"
```

Expected response should show:
```json
{
  "driver": "gmail",
  "smtp_host": "smtp.gmail.com",
  "smtp_port": 587,
  "encryption": "tls",
  "enable_smtp": 1
}
```

## Direct Database Update (Emergency Only)
If needed, you can update directly via Laravel Tinker:
```bash
php artisan tinker
```

```php
$config = \App\Models\EmailConfig::where('driver', 'gmail')->first();
$config->smtp_host = 'smtp.gmail.com';
$config->smtp_port = 587;
$config->encryption = 'tls';
$config->enable_smtp = 1;
$config->password = \Illuminate\Support\Facades\Crypt::encryptString('your-app-password');
$config->save();
```

## Important Notes
1. **Never use your regular Gmail password** - Always use App Passwords
2. **Keep App Password secure** - Treat it like your main password
3. **Test after setup** - Always test the configuration after changes
4. **Check spam folder** - Test emails might go to spam initially