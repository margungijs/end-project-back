<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Models\User;
use App\Events\MessageSent;

class ChatController extends Controller
{
    public static function send(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        if($request->user_id) {
            $message = Conversation::create([
                'user_id' => $request->user_id,
                'friend_id' => $request->user()->id,
                'message' => $request->message,
                'sender' => $request->user()->id,
            ]);

            $user_id = min($request->user_id, $request->user()->id);
            $friend_id = max($request->user_id, $request->user()->id);

            broadcast(new MessageSent($user_id, $request->message, $friend_id, $request->user()->id))->toOthers();

            return response()->json($message);
        } else {
            $message = Conversation::create([
                'user_id' => $request->user()->id,
                'friend_id' => $request->friend_id,
                'message' => $request->message,
                'sender' => $request->user()->id,
            ]);

            $user_id = min($request->user()->id, $request->friend_id);
            $friend_id = max($request->user()->id, $request->friend_id);

            broadcast(new MessageSent($user_id, $request->message, $friend_id, $request->user()->id))->toOthers();

            return response()->json($message);
        }
    }

    public function fetch(Request $request, $friend_id)
    {
        $user_id = $request->user()->id;

        $friendshipStatus = $request->user()->getFriendshipStatus($friend_id);

        if(!$friendshipStatus){
            return response()->json([]);
        }

        $messages = Conversation::where(function($query) use ($user_id, $friend_id) {
            $query->where('user_id', $user_id)
                ->where('friend_id', $friend_id);
        })->orWhere(function($query) use ($user_id, $friend_id) {
            $query->where('user_id', $friend_id)
                ->where('friend_id', $user_id);
        })->orderBy('created_at')
        ->get();

        $messagesWithSequentialId = $messages->map(function($message, $index) {
            $message->conversation_id = $index + 1;
            return $message;
        });

        return response()->json($messagesWithSequentialId);
    }
}
