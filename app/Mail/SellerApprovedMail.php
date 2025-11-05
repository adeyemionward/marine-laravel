<?php

namespace App\Mail;

use App\Models\SellerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;

    public function __construct(SellerApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        return $this->subject('✅ Your Seller Application Is Approved')
            ->view('emails.seller-approved')
            ->with([
                'user' => $this->application->user,
                'application' => $this->application
            ]);
    }
}
