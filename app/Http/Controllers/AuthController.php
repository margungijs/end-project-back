<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'password' => 'required',
        ]);

        if($validation->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'The payload is not formatted correctly',
                'errors' => $validation->errors()
            ], 422);
        }

        $credentials = $request->only('name', 'password');

        if(! Auth::attempt($credentials)){
            $name = User::where('name', $credentials['name'] ?? '')->first();

            if(!$name){
                return response()->json([
                    'error' => 'Username not found',
                    'status' => 401,
                ], 201);
            }
        }else{
            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        }

        return response()->json([
            'password' => 'Password incorrect',
            'status' => 401
        ], 201);
    }

    public function destroy(Request $request){
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
