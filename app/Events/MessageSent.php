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
        \Log::info("Broadcasting to channel: chat-room-message.{$this->message->chat_room_id}");
        return new Channel('chat-room-message.' . $this->message->chat_room_id);
    }

    public function broadcastAs()
    {
        return 'new-message';
    }

    public function broadcastWith()
    {
        $payload = [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'message' => $this->message->message,
                'created_at' => $this->message->created_at->toISOString(),
                'chat_room_id' => $this->message->chat_room_id, // Tambahkan chat_room_id
            ]
        ];
        \Log::debug("Broadcast payload:", $payload);
        return $payload;
    }
}