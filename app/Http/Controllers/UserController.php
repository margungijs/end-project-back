<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request){
        $validation = Validator::make($request->all(), [
            'name' => ["required", "unique:users,name", "between:3,30, 'regex:/^[a-zA-Z0-9\s]+$/'"],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols(),
            ],
            [
                'name.required' => "Username field is required",
                'email.required' => "Email field is required",
                'password.required' => "Password field is required",
                'name.unique' => 'Name is already taken',
                'email.unique' => 'Email already taken',
                'email.email' => 'Email must be a valid email address',
                'password.confirm' => "Passwords don't match",
                'password.min' => "Password must be at least 8 characters long",
                'password.letters' => "Password must contain at least one letter",
                'password.mixedCase' => "Password must contain at least one upper and lower case letter",
                'password.numbers' => "Password must contain at least one number",
                'password.symbols' => "Password must contain at least one symbol"
            ]
        ]);

        if($validation->fails()){
            return response()->json([
                'status' => 422,
                'message' => 'The payload is not formatted correctly',
                'errors' => $validation->errors()
            ], 201);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'birthdate' => $request->birthdate
        ]);

        event(new Registered($user));

        return response()->json([
            'status' => 201,
            'success_message' => 'User created succesfully'
        ], 201);
    }
}
