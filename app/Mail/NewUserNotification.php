<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     *
     * @param $user  The registered user
     * @param $url   The email verification URL
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
        return $this->subject('🎉 Welcome to Marine.ng')
                    ->view('emails.new_user_notification') // Blade view we created
                    ->with([
                        'userName' => $this->user->profile->full_name ?? $this->user->name,
                    ]);
    }
}
