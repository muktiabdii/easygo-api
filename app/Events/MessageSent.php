<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    // constructor untuk mengirim data message
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    // channel yang digunakan untuk broadcast
    public function broadcastOn()
    {
        return new Channel('chat.' . $this->message->chat_room_id);
    }

    // format data yang dikirim ke frontend
    public function broadcastWith()
    {
        return [
            'message' => $this->message->message,
            'sender_name' => $this->message->sender->name,
            'created_at' => $this->message->created_at->format('H:i'),
        ];
    }
}
