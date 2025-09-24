<?php

namespace App\Events;

use App\Models\Message;
use App\Models\UserProfile;
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
    public UserProfile $sender;

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
                    'name' => $this->sender->full_name,
                    'company' => $this->sender->company_name,
                    'is_verified' => $this->sender->is_verified,
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
