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
        $friendRequest = friendship::where('friend_id', $request->user()->id)
            ->where('user_id', $request->user_id)
            ->first();

        if($friendRequest){
            $friendRequest->update([
                'status' => 1
            ]);

            return response()->noContent();
        }else{
            return response()->json(['error' => 'Friend request not found'], 404);
        }
    }

    public static function delete(Request $request){
        if($request->user_id){
            friendship::where('friend_id', $request->user()->id)
                ->where('user_id', $request->user_id)
                ->delete();
            return response()->noContent();
        }else if($request->friend_id){
            friendship::where('user_id', $request->user()->id)
                ->where('friend_id', $request->friend_id)
                ->delete();
            return response()->noContent();
        }

        return response()->noContent();
    }
}
