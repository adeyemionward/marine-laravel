<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMTP Test Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            color: #1e40af;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .content {
            color: #333;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .success {
            background-color: #10b981;
            color: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Marine.africa</h1>
            <h2>SMTP Configuration Test</h2>
        </div>

        <div class="content">
            <div class="success">
                ✅ Email Configuration Test Successful!
            </div>

            <p>Hello,</p>

            <p>This is a test email from Marine.africa to verify that your SMTP email configuration is working correctly.</p>

            <p><strong>Test Details:</strong></p>
            <ul>
                <li>Test sent at: {{ now()->format('Y-m-d H:i:s') }}</li>
                <li>Application: Marine.africa Admin Panel</li>
                <li>Purpose: SMTP Configuration Verification</li>
            </ul>

            <p>If you received this email, your SMTP configuration is working properly and you can now send newsletters and other email communications through your Marine.africa platform.</p>

            <p>Thank you for using Marine.africa!</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Marine.africa - Africa's Premier Marine Equipment Marketplace</p>
            <p>This is an automated test email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>