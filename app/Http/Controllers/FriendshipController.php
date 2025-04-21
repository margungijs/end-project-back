<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\friendship;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

    public static function delete(Request $request)
    {
        $authUserId = $request->user()->id;
        $friendId = $request->user_id ?? $request->friend_id;

        if (!$friendId) {
            return response()->json(['error' => 'Friend ID is required'], 400);
        }

        friendship::where(function ($query) use ($authUserId, $friendId) {
            $query->where('user_id', $authUserId)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($authUserId, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $authUserId);
        })->delete();

        return response()->noContent();
    }


    public static function fetch(Request $request)
    {
        $userId = $request->user()->id;

        $friendships = DB::table('friendships')
            ->where('status', 1)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('friend_id', $userId);
            })
            ->get();

        $friendData = $friendships->map(function ($friendship) use ($userId) {
            return [
                'friend_id' => $friendship->user_id == $userId ? $friendship->friend_id : $friendship->user_id,
                'became_friends_at' => $friendship->updated_at, // The time they became friends
            ];
        });

        $friends = User::whereIn('id', $friendData->pluck('friend_id'))->get();

        $friendsWithTimestamps = $friends->map(function ($friend) use ($friendData) {
            $friendInfo = $friendData->firstWhere('friend_id', $friend->id);
            return array_merge($friend->toArray(), [
                'became_friends_at' => $friendInfo['became_friends_at'],
            ]);
        });

        return response()->json($friendsWithTimestamps);
    }
}
