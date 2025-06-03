<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\ChatRoom;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Update last_active
        Auth::user()->update(['last_active' => now()]);

        $chats = ChatRoom::where('user1_id', $userId)
            ->orWhere('user2_id', $userId)
            ->with([
                'user1:id,name',
                'user2:id,name',
                'messages' => function ($query) {
                    $query->latest()->limit(1);
                },
                'messages.sender:id,name'
            ])
            ->get();

        $chatsWithLastMessage = $chats->map(function ($chat) {
            $lastMessage = $chat->messages->first();

            $formattedLastMessage = null;

            if ($lastMessage) {
                $createdAt = Carbon::parse($lastMessage->created_at)->setTimezone('Asia/Jakarta');
                $updatedAt = Carbon::parse($lastMessage->updated_at)->setTimezone('Asia/Jakarta');

                $createdAtFormatted = $createdAt->toISOString();
                $updatedAtFormatted = $updatedAt->toISOString();

                $formattedLastMessage = [
                    'id' => $lastMessage->id,
                    'chat_room_id' => $lastMessage->chat_room_id,
                    'sender_id' => $lastMessage->sender_id,
                    'message' => $lastMessage->message,
                    'created_at' => $createdAtFormatted,
                    'updated_at' => $updatedAtFormatted,
                    'sender' => $lastMessage->sender
                ];
            }

            return [
                'chat_room_id' => $chat->id,
                'user1' => $chat->user1,
                'user2' => $chat->user2,
                'last_message' => $formattedLastMessage,
            ];
        });

        \Log::info("Chat rooms fetched:", $chatsWithLastMessage->toArray());
        return response()->json($chatsWithLastMessage);
    }

    public function messages($chatRoomId)
    {
        // Update last_active
        Auth::user()->update(['last_active' => now()]);

        Carbon::setLocale('id');

        $messages = Message::where('chat_room_id', $chatRoomId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        $groupedMessages = [];

        foreach ($messages as $message) {
            $createdAt = Carbon::parse($message->created_at)->setTimezone('Asia/Jakarta');

            $dateKey = $createdAt->isToday() ? 'Hari Ini' :
                ($createdAt->isYesterday() ? 'Kemarin' :
                    $createdAt->translatedFormat('l, j F Y'));

            $timeFormatted = $createdAt->format('H:i');

            $groupedMessages[$dateKey][] = [
                'id' => $message->id,
                'chat_room_id' => $message->chat_room_id,
                'sender_id' => $message->sender_id,
                'message' => $message->message,
                'created_at' => $createdAt->toISOString(),
                'time' => $timeFormatted,
                'sender' => $message->sender,
            ];
        }

        \Log::info("Messages fetched for chat room {$chatRoomId}:", $groupedMessages);
        return response()->json($groupedMessages);
    }

    public function sendMessage(Request $request, $chatRoomId)
    {
        \Log::info("Received sendMessage request:", ['chatRoomId' => $chatRoomId, 'message' => $request->message, 'userId' => Auth::id()]);
        $request->validate(['message' => 'required|string']);
        $chatRoom = ChatRoom::find($chatRoomId);
        if (!$chatRoom) {
            \Log::error("Chat room not found: {$chatRoomId}");
            return response()->json(['error' => 'Chat room not found'], 404);
        }

        // Update last_active
        Auth::user()->update(['last_active' => now()]);

        try {
            $message = Message::create([
                'chat_room_id' => $chatRoomId,
                'sender_id' => Auth::id(),
                'message' => $request->message,
                'created_at' => now('Asia/Jakarta'),
            ]);
            \Log::info("Message created:", ['room' => $chatRoomId, 'message' => $message->toArray()]);
            try {
                broadcast(new \App\Events\MessageSent($message))->toOthers();
                \Log::info("Broadcast initiated for message:", ['messageId' => $message->id, 'channel' => 'chat-room-message.' . $chatRoomId]);
            } catch (\Exception $e) {
                \Log::error("Broadcast failed:", ['error' => $e->getMessage(), 'messageId' => $message->id]);
            }
            return response()->json([
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'message' => $message->message,
                'created_at' => $message->created_at->toISOString(),
                'time' => $message->created_at->format('H:i'),
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to create message:", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    public function createRoom(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // Update last_active
        Auth::user()->update(['last_active' => now()]);

        $user1 = Auth::id();
        $user2 = $request->user_id;

        $room = ChatRoom::where(function ($q) use ($user1, $user2) {
            $q->where('user1_id', $user1)->where('user2_id', $user2);
        })->orWhere(function ($q) use ($user1, $user2) {
            $q->where('user1_id', $user2)->where('user2_id', $user1);
        })->first();

        if (!$room) {
            $room = ChatRoom::create([
                'user1_id' => $user1,
                'user2_id' => $user2
            ]);
            broadcast(new \App\Events\RoomCreated($room));
        }

        $room->load('user1:id,name', 'user2:id,name');

        return response()->json([
            'id' => $room->id,
            'chat_room_id' => $room->id,
            'user1' => $room->user1,
            'user2' => $room->user2,
            'last_message' => null,
        ]);
    }

    public function searchMessages(Request $request)
    {
        $request->validate(['keyword' => 'required|string|min:1']);
        $keyword = $request->keyword;
        $userId = auth()->id();

        // Update last_active
        Auth::user()->update(['last_active' => now()]);

        $messages = Message::where(function ($query) use ($userId) {
            $query->whereHas('chatRoom', function ($q) use ($userId) {
                $q->where('user1_id', $userId)->orWhere('user2_id', $userId);
            });
        })
            ->where('message', 'like', '%' . $keyword . '%')
            ->with(['sender', 'chatRoom'])
            ->orderBy('created_at', 'asc')
            ->get();

        $groupedMessages = [];
        foreach ($messages as $message) {
            $createdAt = Carbon::parse($message->created_at)->setTimezone('Asia/Jakarta');
            $dateKey = $createdAt->isToday() ? 'Hari Ini' :
                ($createdAt->isYesterday() ? 'Kemarin' :
                    $createdAt->translatedFormat('l, j F Y'));
            $groupedMessages[$dateKey][] = [
                'id' => $message->id,
                'chat_room_id' => $message->chat_room_id,
                'sender_id' => $message->sender_id,
                'message' => $message->message,
                'created_at' => $createdAt->toISOString(),
                'time' => $createdAt->format('H:i'),
                'sender' => $message->sender,
                'chat_room' => $message->chatRoom,
            ];
        }

        return response()->json($groupedMessages);
    }
}