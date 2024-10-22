<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function fetch(Request $request, $id){
        $user = User::find($id);

        if(!$user){
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        $friendshipStatus = $request->user()->getFriendshipStatus($id);

        return response()->json([
            'user' => $user,
            'status' => $friendshipStatus
        ], 200);
    }
}
