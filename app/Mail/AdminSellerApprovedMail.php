<?php

namespace App\Mail;

use App\Models\SellerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminSellerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $adminNotes;

    public function __construct(SellerApplication $application, $adminNotes = null)
    {
        $this->application = $application;
        $this->adminNotes = $adminNotes;
    }

    public function build()
    {
        return $this->subject('Seller Application Approved - ' . $this->application->business_name)
            ->view('emails.admin-seller-approved')
            ->with([
                'application' => $this->application,
                'adminNotes' => $this->adminNotes
            ]);
    }
}
