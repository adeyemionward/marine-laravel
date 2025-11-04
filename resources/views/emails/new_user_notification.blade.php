<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Marine.ng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .button {
            display: inline-block;
            padding: 12px 20px;
            margin: 20px 0;
            background-color: #1d72b8;
            color: #fff !important;
            text-decoration: none;
            border-radius: 5px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome to Marine.ng!</h2>

        <p>Thank you for creating an account with Marine.ng, Nigeria's premier marine equipment marketplace.</p>

        {{-- <h3>Why verify your email?</h3> --}}
        <ul>
            <li>Access all platform features</li>
            <li>Receive important account notifications</li>
            <li>Buy and sell marine equipment securely</li>
            <li>Connect with trusted suppliers and buyers</li>
        </ul>


        <ol>
            <li>Complete your profile for better visibility</li>
            <li>Browse our extensive marine equipment catalog</li>
            <li>Start buying or selling marine equipment</li>
            <li>Connect with other marine professionals</li>
        </ol>

        <p>If you did not create an account, no further action is required.</p>

        <p>Welcome aboard,</p>
        <p>The Marine.ng Team</p>

        <div class="footer">
            &copy; {{ date('Y') }} Marine.ng. All rights reserved.
        </div>
    </div>
</body>
</html>
