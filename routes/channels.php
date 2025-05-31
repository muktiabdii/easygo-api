<?php

use App\Models\ChatRoom;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat-room-message.{chatRoomId}', function ($user, $chatRoomId) {
    return Auth::check() && ChatRoom::where('id', $chatRoomId)
        ->where(function ($query) use ($user) {
            $query->where('user1_id', $user->id)
                  ->orWhere('user2_id', $user->id);
        })->exists();
});