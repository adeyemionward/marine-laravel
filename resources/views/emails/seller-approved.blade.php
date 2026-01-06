<style>
     .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
</style>
<h2>Congratulations {{ $user->name }},</h2>
<p>Your seller application has been <strong>approved</strong>.</p>

<p>You now have access to your seller dashboard on Marine.africa. Start uploading your marine products and services!</p>

<p>If you need help, our support team is available at <b>support@marine.africa</b></p>

<p>Welcome aboard! 🚢</p>
<p><strong>Marine.africa Team</strong></p>
<div class="footer">
    <p> This is an automated notification from Marine.africa.</p>
    &copy; {{ date('Y') }} Marine.africa. All rights reserved.
</div>
