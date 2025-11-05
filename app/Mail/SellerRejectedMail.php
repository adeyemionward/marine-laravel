<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SellerApplication;

class SellerRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $reason;

    public function __construct(SellerApplication $application, $reason)
    {
        $this->application = $application;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('Your Seller Application Status')
            ->markdown('emails.seller-rejected');
    }
}
