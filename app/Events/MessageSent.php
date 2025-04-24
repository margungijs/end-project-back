<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $message;
    public $friend;
    public $sender;

    /**
     * Create a new event instance.
     */
    public function __construct($user, $message, $friend, $sender)
    {
        $this->user = $user;
        $this->message = $message;
        $this->friend = $friend;
        $this->sender = $sender;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return ['chat.' . $this->user . '.' . $this->friend];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
