<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortcutController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SearchController;

Route::prefix('authenticated')->group(function () {
    Route::get('/user', function (Request $request) {
        if($request->user()){
            return response()->json([
                'status' => 201,
                'user' => $request->user()->load(['shortcuts', 'requests', 'friendsAsUser', 'friendsAsFriend', 'posts', 'templates', 'postLimit']),
            ], 201);
        }else{
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }
    });

    Route::get('/shortcuts', function (Request $request) {
        return response()->json([
            'status' => 201,
            'shortcuts' => $request->user()->shortcuts,
        ], 201);
    });

    Route::get('/getUser/{id}', [UserController::class, 'fetch']);

    Route::post('/shortcut', [ShortcutController::class, 'store']);

    Route::post('/removeShortcut', [ShortcutController::class, 'delete']);

    Route::post('/image', [ImageController::class, 'store']);

    Route::post('/friendAdd', [FriendshipController::class, 'store']);

    Route::post('/friendAccept', [FriendshipController::class, 'accept']);

    Route::post('/removeFriend', [FriendshipController::class, 'delete']);

    Route::post('/template', [TemplateController::class, 'store']);

    Route::get('/templates', [TemplateController::class, 'fetch']);

    Route::post('/post', [PostController::class, 'store']);

    Route::post('/postImage', [PostController::class, 'postImage']);

    Route::get('/feed', [UserController::class, 'feed']);

    Route::post('/sendMessage', [ChatController::class, 'send']);

    Route::get('/messages/{friend_id}', [ChatController::class, 'fetch']);

    Route::post('/edit', [UserController::class, 'edit']);

    Route::get('/fetchPosts', [PostController::class, 'fetch']);

    Route::post('/postLike', [PostController::class, 'like']);

    Route::post('/templateLike', [TemplateController::class, 'like']);

    Route::get('/explore', [UserController::class, 'explore']);

    Route::get('/collection', [UserController::class, 'collection']);

    Route::get('/friends', [FriendshipController::class, 'fetch']);

    Route::post('/templateView', [TemplateController::class, 'view']);

    Route::post('/postView', [PostController::class, 'view']);

    Route::get('/search', [SearchController::class, 'search']);

    Route::get('/searchSpecific', [SearchController::class, 'searchSpecific']);
})->middleware('auth:sanctum');

