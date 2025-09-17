<?php

namespace App\Events;

use App\Models\UserProfile;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public UserProfile $user;
    public int $conversationId;
    public bool $isTyping;

    /**
     * Create a new event instance.
     */
    public function __construct(UserProfile $user, int $conversationId, bool $isTyping = true)
    {
        $this->user = $user;
        $this->conversationId = $conversationId;
        $this->isTyping = $isTyping;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
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
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->full_name,
            ],
            'conversation_id' => $this->conversationId,
            'is_typing' => $this->isTyping,
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return $this->isTyping ? 'user.typing' : 'user.stopped-typing';
    }
}
