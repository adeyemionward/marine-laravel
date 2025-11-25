<style>
     .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
</style>
<h3>Seller Application Approved</h3>

<p><strong>Business Name:</strong> {{ $application->business_name }}</p>
<p><strong>Applicant:</strong> {{ $application->user->name }} ({{ $application->user->email }})</p>

@if($adminNotes)
<p><strong>Admin Notes:</strong> {{ $adminNotes }}</p>
@endif

<p>Approval has been processed successfully.</p>
<div class="footer">
    <p> This is an automated notification from Marine.africa.</p>
    Marine.africa.
</div>
