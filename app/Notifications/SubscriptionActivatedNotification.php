<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $subscription;
    private $plan;

    public function __construct(Subscription $subscription, SubscriptionPlan $plan)
    {
        $this->subscription = $subscription;
        $this->plan = $plan;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🎉 Welcome to Marine.africa - Your Subscription is Active!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Congratulations! Your Marine.africa seller subscription has been successfully activated.')
            ->line('**Subscription Details:**')
            ->line('Plan: ' . $this->plan->name)
            ->line('Status: Active')
            ->line('Expires: ' . $this->subscription->expires_at->format('F j, Y'))
            ->line('You can now:')
            ->line('• List unlimited marine equipment')
            ->line('• Access advanced seller tools')
            ->line('• Receive customer inquiries')
            ->line('• Track your sales performance')
            ->action('Go to Seller Dashboard', url('/seller-dashboard'))
            ->line('Thank you for choosing Marine.africa as your marine equipment marketplace!')
            ->salutation('Best regards,')
            ->salutation('The Marine.africa Team');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Subscription Activated',
            'message' => "Your {$this->plan->name} subscription is now active until {$this->subscription->expires_at->format('F j, Y')}",
            'type' => 'subscription_activated',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->plan->name,
            'expires_at' => $this->subscription->expires_at,
        ];
    }
}