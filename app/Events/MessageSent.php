<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        $channel = 'chat-room-message.' . $this->message->chat_room_id;
        \Log::info("Broadcasting to channel:", ['channel' => $channel]);
        return new Channel($channel);
    }

    public function broadcastAs()
    {
        return 'new-message';
    }

    public function broadcastWith()
    {
        $createdAt = \Carbon\Carbon::parse($this->message->created_at)->setTimezone('Asia/Jakarta');
        $payload = [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'message' => $this->message->message,
                'created_at' => $createdAt->toISOString(),
                'chat_room_id' => $this->message->chat_room_id,
                'time' => $createdAt->format('H:i'),
            ]
        ];
        \Log::debug("Broadcast payload:", $payload);
        return $payload;
    }
}