<style>
     .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
</style>
<p><strong>New Seller Application Submitted</strong></p>

<p>A new seller has applied:</p>

<p>
Name: {{ $user->name }}<br>
Email: {{ $user->email }}<br>
Business: {{ $application->business_name }}
</p>

<p>Please log in to the admin dashboard to review this application.</p>

<div class="footer">
    This is an automated notification from Marine.ng.
</div>

