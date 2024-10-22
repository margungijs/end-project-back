<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortcutController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\UserController;

Route::prefix('authenticated')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 201,
            'user' => $request->user()->load(['shortcuts', 'requests', 'friendsAsUser', 'friendsAsFriend']),
        ], 201);
    });

    Route::get('/shortcuts', function (Request $request) {
        return response()->json([
            'status' => 201,
            'shortcuts' => $request->user()->shortcuts,
        ], 201);
    });

    Route::get('/getUser/{id}', [UserController::class, 'fetch']);

    Route::post('/shortcut', [ShortcutController::class, 'store']);

    Route::post('/image', [ImageController::class, 'store']);

    Route::post('/friendAdd', [FriendshipController::class, 'store']);

    Route::post('/friendAccept', [FriendshipController::class, 'accept']);

    Route::post('/removeFriend', [FriendshipController::class, 'delete']);
})->middleware('auth:sanctum');
