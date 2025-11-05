<?php

namespace App\Mail;

use App\Models\SellerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewSellerApplicationAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $application;

    public function __construct(SellerApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        return $this->subject('New Seller Application Notification')
            ->view('emails.new_seller_admin')
            ->with([
                'user' => $this->application->user,
                'application' => $this->application,
            ]);
    }
}
