<?php

namespace App\Notifications;

use App\Models\Banner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BannerRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $banner;
    private $rejectionReason;

    public function __construct(Banner $banner, string $rejectionReason)
    {
        $this->banner = $banner;
        $this->rejectionReason = $rejectionReason;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('❌ Banner Advertisement Rejected - Marine.africa')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Unfortunately, your banner advertisement has been rejected.')
            ->line('**Banner Details:**')
            ->line('Title: ' . $this->banner->title)
            ->line('Type: ' . ucfirst($this->banner->banner_type))
            ->line('Position: ' . ucfirst($this->banner->position))
            ->line('**Rejection Reason:**')
            ->line($this->rejectionReason)
            ->line('**What happens next:**')
            ->line('• Your payment of ₦' . number_format($this->banner->purchase_price, 2) . ' will be refunded')
            ->line('• The refund will be processed within 3-5 business days')
            ->line('• You can create a new banner that meets our guidelines')
            ->line('**Common rejection reasons:**')
            ->line('• Content violates our advertising policies')
            ->line('• Poor image quality or inappropriate content')
            ->line('• Misleading or false advertising claims')
            ->line('• Technical issues with media files')
            ->action('View Advertising Guidelines', url('/advertising-guidelines'))
            ->line('If you have questions about this rejection, please contact our support team.')
            ->salutation('Best regards,')
            ->salutation('The Marine.africa Team');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Banner Rejected',
            'message' => "Your banner '{$this->banner->title}' was rejected. Reason: {$this->rejectionReason}",
            'type' => 'banner_rejected',
            'banner_id' => $this->banner->id,
            'banner_title' => $this->banner->title,
            'rejection_reason' => $this->rejectionReason,
            'refund_amount' => $this->banner->purchase_price,
            'action_url' => url('/advertising-guidelines'),
        ];
    }
}