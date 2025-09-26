<?php

namespace App\Notifications;

use App\Models\Banner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BannerApprovalRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $banner;

    public function __construct(Banner $banner)
    {
        $this->banner = $banner;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🔔 New Banner Requires Approval - Marine.ng Admin')
            ->greeting('Hello Admin!')
            ->line('A new banner advertisement has been purchased and requires your approval.')
            ->line('**Banner Details:**')
            ->line('Title: ' . $this->banner->title)
            ->line('Type: ' . ucfirst($this->banner->banner_type))
            ->line('Position: ' . ucfirst($this->banner->position))
            ->line('Purchaser: ' . $this->banner->purchaser->name)
            ->line('Amount Paid: ₦' . number_format($this->banner->purchase_price, 2))
            ->line('Duration: ' . $this->banner->duration_days . ' days')
            ->line('Scheduled Start: ' . $this->banner->start_date->format('F j, Y'))
            ->action('Review Banner', url('/admin-dashboard'))
            ->line('Please review and approve or reject this banner advertisement.')
            ->salutation('Marine.ng Admin System');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Banner Approval Required',
            'message' => "New {$this->banner->banner_type} banner '{$this->banner->title}' requires approval",
            'type' => 'banner_approval_required',
            'banner_id' => $this->banner->id,
            'banner_type' => $this->banner->banner_type,
            'purchaser_name' => $this->banner->purchaser->name,
            'amount' => $this->banner->purchase_price,
            'action_url' => url('/admin-dashboard'),
        ];
    }
}