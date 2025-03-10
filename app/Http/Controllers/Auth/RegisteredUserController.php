<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Nette\Schema\ValidationException;
use Illuminate\Http\JsonResponse;
use App\Models\PostLimit;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:'.User::class],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->string('password')),
            ]);

            PostLimit::create([
                'user_id' => $user->id,
                'limit' => 1,
                'posts' => []
            ]);

            event(new Registered($user));

            Auth::login($user);

            return response()->noContent();
        }catch (ValidationException $e){
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        }

    }
}
