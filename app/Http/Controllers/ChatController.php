<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\ChatRoom;
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

                // format waktu jika hari ini
                $createdAtFormatted = $createdAt->isToday()
                    ? $createdAt->format('H:i') // misal "14:30"
                    : $createdAt->diffForHumans(); // misal "2 hari yang lalu"

                // format waktu jika hari ini
                $updatedAtFormatted = $updatedAt->isToday()
                    ? $updatedAt->format('H:i')
                    : $updatedAt->diffForHumans();

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

        return response()->json($chatsWithLastMessage);
    }

    // method untuk ambil semua pesan dalam chat room
    public function messages($chatRoomId)
    {

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
            } 
            
            elseif ($createdAt->isYesterday()) {
                $dateKey = 'Kemarin';
            } 
            
            else {
                $dateKey = $createdAt->translatedFormat('l, j F Y'); // contoh: Sabtu, 3 Mei 2025
            }

            // format jam biasa untuk bubble chat
            $timeFormatted = $createdAt->format('H:i');

            // menambahkan pesan ke dalam array yang sudah dikelompokkan
            $groupedMessages[$dateKey][] = [
                'id' => $message->id,
                'chat_room_id' => $message->chat_room_id,
                'sender_id' => $message->sender_id,
                'message' => $message->message,
                'time' => $timeFormatted,
                'sender' => $message->sender,
            ];
        }

        return response()->json($groupedMessages);
    }

    // method untuk mengirim pesan
    public function sendMessage(Request $request, $chatRoomId)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $message = Message::create([
            'chat_room_id' => $chatRoomId,
            'sender_id' => Auth::id(),
            'message' => $request->message
        ]);

        return response()->json($message);
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
}
