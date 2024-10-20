<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\friendship;

class FriendshipController extends Controller
{
    public static function store(Request $request){
        friendship::create([
            'user_id' => $request->user()->id,
            'friend_id' => $request->friend_id,
            'privacy' => 0,
            'date' => now(),
            'status' => 0
        ]);

        return response()->noContent();
    }

    public static function accept(Request $request){
        friendship::where('friend_id', $request->user()->id)
            ->where('user_id', $request->user_id)
            ->update([
                'status' => 1
            ]);

        return response()->noContent();
    }

    public static function delete(Request $request){
        friendship::where('user_id', $request->user()->id)
            ->where('friend_id', $request->friend_id)
            ->orWhere('friend_id', $request->user()->id)
            ->where('user_id', $request->friend_id)
            ->delete();

        return response()->noContent();
    }
}
