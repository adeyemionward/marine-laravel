<style>
     .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
     }
</style>

<h2>Hello {{ $application->user->name }},</h2>

<p>Thank you for applying to become a seller on <strong>Marine.ng</strong>.</p>

<p>After reviewing your application, we regret to inform you that it has been <strong>rejected</strong> at this time.</p>

<p><strong>Reason:</strong></p>
<p style="background: #fff4f4; padding: 10px; border-left: 4px solid #cc0000;">
    {{ $reason }}
</p>

<p>You may reapply in the future or reach out to us if you believe this is an error.</p>

<p>If you need assistance, our team is available at <strong>info@marine.ng</strong></p>

<p>Thank you for your interest in joining Marine.ng 🚢</p>

<p><strong>Marine.ng Team</strong></p>

<div class="footer">
    <p>This is an automated notification from Marine.ng.</p>
    &copy; {{ date('Y') }} Marine.ng. All rights reserved.
</div>
