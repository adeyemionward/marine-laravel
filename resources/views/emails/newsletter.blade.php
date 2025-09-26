<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marine.ng Newsletter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f8fafc;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
        .newsletter-content {
            background: white;
            padding: 1px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Marine.ng Newsletter</h1>
        <p>Your trusted source for marine equipment and updates</p>
    </div>

    <div class="content">
        <div class="newsletter-content">
            {!! $content !!}
        </div>
    </div>

    <div class="footer">
        <p>Thank you for subscribing to Marine.ng Newsletter</p>
        <p>Marine.ng - Your premier destination for marine equipment</p>
        <p><a href="{{ config('app.frontend_url', 'https://marine.ng') }}" style="color: #2563eb; text-decoration: none;">Visit our website</a></p>
        <p>© {{ date('Y') }} Marine.ng. All rights reserved.</p>
    </div>
</body>
</html>