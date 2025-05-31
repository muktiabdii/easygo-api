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
    // method untuk ambil semua chat room
    public function index()
    {
        $userId = Auth::id();

        // ambil semua chat room yang dimiliki user
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

        // menampilkan tampilan chat room dengan pesan terakhir
        $chatsWithLastMessage = $chats->map(function ($chat) {
            $lastMessage = $chat->messages->first();

            $formattedLastMessage = null;

            // format pesan terakhir
            if ($lastMessage) {
                $createdAt = Carbon::parse($lastMessage->created_at)->setTimezone('Asia/Jakarta');
                $updatedAt = Carbon::parse($lastMessage->updated_at)->setTimezone('Asia/Jakarta');

                // gunakan format ISO 8601 untuk created_at dan updated_at
                $createdAtFormatted = $createdAt->toISOString(); // misal: "2025-05-31T13:48:00.000Z"
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

            // mengembalikan data chat room dengan pesan terakhir
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

    // method untuk ambil semua pesan dalam chat room
    public function messages($chatRoomId)
    {
        // set lokal ke bahasa Indonesia
        Carbon::setLocale('id');

        // mengambil semua pesan dalam chat room
        $messages = Message::where('chat_room_id', $chatRoomId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // array untuk menyimpan pesan yang sudah dikelompokkan
        $groupedMessages = [];

        // mengelompokkan pesan berdasarkan tanggal
        foreach ($messages as $message) {
            $createdAt = Carbon::parse($message->created_at)->setTimezone('Asia/Jakarta');

            // pengecekan untuk menentukan header tanggal
            if ($createdAt->isToday()) {
                $dateKey = 'Hari Ini';
            } elseif ($createdAt->isYesterday()) {
                $dateKey = 'Kemarin';
            } else {
                $dateKey = $createdAt->translatedFormat('l, j F Y'); // misal: Jumat, 16 Mei 2025
            }

            // format jam biasa untuk bubble chat
            $timeFormatted = $createdAt->format('H:i');

            // menambahkan pesan ke dalam array yang sudah dikelompokkan
            $groupedMessages[$dateKey][] = [
                'id' => $message->id,
                'chat_room_id' => $message->chat_room_id,
                'sender_id' => $message->sender_id,
                'message' => $message->message,
                'created_at' => $createdAt->toISOString(), // tambahkan created_at dalam format ISO 8601
                'time' => $timeFormatted,
                'sender' => $message->sender,
            ];
        }

        \Log::info("Messages fetched for chat room {$chatRoomId}:", $groupedMessages);
        return response()->json($groupedMessages);
    }

    // method untuk mengirim pesan
    public function sendMessage(Request $request, $chatRoomId)
    {
        $request->validate(['message' => 'required|string']);
        $chatRoom = ChatRoom::find($chatRoomId);
        if (!$chatRoom) {
            \Log::error("Chat room not found: {$chatRoomId}");
            return response()->json(['error' => 'Chat room not found'], 404);
        }
        $message = Message::create([
            'chat_room_id' => $chatRoomId,
            'sender_id' => Auth::id(),
            'message' => $request->message,
            'created_at' => now('Asia/Jakarta'),
        ]);
        \Log::info("Message created:", ['room' => $chatRoomId, 'message' => $message->toArray()]);
        broadcast(new MessageSent($message));
        return response()->json([
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'message' => $message->message,
            'created_at' => $message->created_at->toISOString(),
        ]);
    }

    // method untuk membuat chat room
    public function createRoom(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user1 = Auth::id();
        $user2 = $request->user_id;

        // mengecek apakah chat room sudah ada
        $room = ChatRoom::where(function ($q) use ($user1, $user2) {
            $q->where('user1_id', $user1)->where('user2_id', $user2);
        })->orWhere(function ($q) use ($user1, $user2) {
            $q->where('user1_id', $user2)->where('user2_id', $user1);
        })->first();

        // jika chat room tidak ada, buat chat room baru
        if (!$room) {
            $room = ChatRoom::create([
                'user1_id' => $user1,
                'user2_id' => $user2
            ]);
        }

        return response()->json($room);
    }

    // method untuk mencari pesan dalam chat room berdasarkan kata kunci
    public function searchMessages(Request $request)
    {
        $request->validate(['keyword' => 'required|string|min:1']);
        $keyword = $request->keyword;
        $userId = auth()->id(); // Mendapatkan ID pengguna yang sedang login

        // Cari pesan di semua chat room yang melibatkan pengguna yang login
        $messages = Message::where(function ($query) use ($userId) {
            $query->whereHas('chatRoom', function ($q) use ($userId) {
                $q->where('user1_id', $userId)->orWhere('user2_id', $userId);
            });
        })
            ->where('message', 'like', '%' . $keyword . '%')
            ->with(['sender', 'chatRoom'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Kelompokkan pesan berdasarkan tanggal
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
                'chat_room' => $message->chatRoom, // Menambahkan info chat room untuk konteks
            ];
        }

        return response()->json($groupedMessages);
    }
}