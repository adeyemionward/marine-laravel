<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
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
            ->subject('⚠️ Marine.africa Subscription Expired - Renew Now')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your Marine.africa seller subscription has expired.')
            ->line('**Subscription Details:**')
            ->line('Plan: ' . $this->plan->name)
            ->line('Status: Expired')
            ->line('Expired on: ' . $this->subscription->expires_at->format('F j, Y'))
            ->line('**What happens now:**')
            ->line('• Your equipment listings have been temporarily hidden')
            ->line('• You cannot receive new customer inquiries')
            ->line('• Seller dashboard features are limited')
            ->line('**To reactivate your account:**')
            ->line('1. Visit your dashboard to see pending invoices')
            ->line('2. Complete payment for subscription renewal')
            ->line('3. Your listings will be automatically restored')
            ->action('Renew Subscription', url('/user-dashboard'))
            ->line('Don\'t lose potential sales - renew your subscription today!')
            ->salutation('Best regards,')
            ->salutation('The Marine.africa Team');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Subscription Expired',
            'message' => "Your {$this->plan->name} subscription expired on {$this->subscription->expires_at->format('F j, Y')}. Renew now to restore your listings.",
            'type' => 'subscription_expired',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->plan->name,
            'expired_at' => $this->subscription->expires_at,
            'action_url' => url('/user-dashboard'),
        ];
    }
}