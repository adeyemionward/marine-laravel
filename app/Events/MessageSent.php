<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public User $sender;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->sender = $message->sender;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        $profile = $this->sender->profile;

        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'type' => $this->message->type,
                'conversation_id' => $this->message->conversation_id,
                'sender_id' => $this->message->sender_id,
                'created_at' => $this->message->created_at->toISOString(),
                'read_at' => $this->message->read_at,
                'offer_price' => $this->message->offer_price,
                'offer_currency' => $this->message->offer_currency,
                'sender' => [
                    'id' => $this->sender->id,
                    'name' => $profile?->full_name ?? $this->sender->name,
                    'company' => $profile?->company_name,
                    'is_verified' => $profile?->is_verified ?? false,
                ],
            ],
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
