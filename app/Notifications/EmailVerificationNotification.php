<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    /**
     * The callback that should be used to create the verify email URL.
     *
     * @var \Closure|null
     */
    public static $createUrlCallback;

    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    public static $toMailCallback;

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        return $this->buildMailMessage($verificationUrl);
    }

    /**
     * Get the verify email notification mail message for the given URL.
     */
    protected function buildMailMessage(string $url): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Welcome to Marine.ng - Verify Your Email')
            ->greeting('Welcome to Marine.ng!')
            ->line('Thank you for creating an account with Marine.ng, Nigeria\'s premier marine equipment marketplace.')
            ->line('**Why verify your email?**')
            ->line('• Access all platform features')
            ->line('• Receive important account notifications')
            ->line('• Buy and sell marine equipment securely')
            ->line('• Connect with trusted suppliers and buyers')
            ->line('Please click the button below to verify your email address and get started:')
            ->action('Verify Email Address', $url)
            ->line('**What\'s next after verification?**')
            ->line('1. Complete your profile for better visibility')
            ->line('2. Browse our extensive marine equipment catalog')
            ->line('3. Start buying or selling marine equipment')
            ->line('4. Connect with other marine professionals')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Welcome aboard,')
            ->salutation('The Marine.ng Team');
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Set a callback that should be used when creating the email verification URL.
     */
    public static function createUrlUsing(\Closure $callback): void
    {
        static::$createUrlCallback = $callback;
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     */
    public static function toMailUsing(\Closure $callback): void
    {
        static::$toMailCallback = $callback;
    }
}
