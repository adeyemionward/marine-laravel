<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     *
     * @param $user  The newly registered user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('🆕 New User Registered on Marine.ng')
                    ->view('emails.new_user_admin')
                    ->with([
                        'fullName' => $this->user->name ?? $this->user->profile->full_name,
                        'email' => $this->user->email,
                        'phone' => $this->user->profile->phone ?? 'N/A',
                        'company' => $this->user->profile->company_name ?? 'N/A',
                        'registeredAt' => $this->user->created_at->format('d M Y, H:i'),
                    ]);
    }
}
