<?php

namespace App\Notifications;

use App\Models\Banner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BannerPaymentConfirmedNotification extends Notification implements ShouldQueue
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
        $statusMessage = $this->banner->auto_approve
            ? 'Your banner is now live!'
            : 'Your banner is pending approval and will be live soon.';

        return (new MailMessage)
            ->subject('✅ Payment Confirmed - Marine.africa Banner Advertisement')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your payment for banner advertisement has been successfully confirmed.')
            ->line('**Banner Details:**')
            ->line('Title: ' . $this->banner->title)
            ->line('Type: ' . ucfirst($this->banner->banner_type))
            ->line('Position: ' . ucfirst($this->banner->position))
            ->line('Amount Paid: ₦' . number_format($this->banner->purchase_price, 2))
            ->line('Duration: ' . $this->banner->duration_days . ' days')
            ->line('Scheduled Start: ' . $this->banner->start_date->format('F j, Y'))
            ->line('Scheduled End: ' . $this->banner->end_date->format('F j, Y'))
            ->line('Status: ' . $statusMessage)
            ->when(!$this->banner->auto_approve, function ($mail) {
                return $mail->line('Our team will review your banner and it will go live once approved (usually within 24 hours).');
            })
            ->action('View Your Banners', url('/user-dashboard'))
            ->line('Thank you for advertising with Marine.africa!')
            ->salutation('Best regards,')
            ->salutation('The Marine.africa Team');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Banner Payment Confirmed',
            'message' => "Payment confirmed for '{$this->banner->title}' banner. " . ($this->banner->auto_approve ? 'Banner is now live!' : 'Awaiting approval.'),
            'type' => 'banner_payment_confirmed',
            'banner_id' => $this->banner->id,
            'banner_title' => $this->banner->title,
            'amount' => $this->banner->purchase_price,
            'status' => $this->banner->status,
            'auto_approved' => $this->banner->auto_approve,
        ];
    }
}