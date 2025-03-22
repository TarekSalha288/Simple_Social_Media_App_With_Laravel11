<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyComment implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;
    protected $commentId;
    protected $userId;
    public function __construct($message,$commentId,$userId)
    {
    $this->message=$message;
    $this->commentId=$commentId;
    $this->userId=$userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notification.'.$this->userId),
        ];
    }
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'commentId' => $this->commentId,
            'userId' => $this->userId,
        ];
    }
}