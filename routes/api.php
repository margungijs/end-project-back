<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortcutController;

Route::prefix('authenticated')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 201,
            'user' => $request->user()->with('shortcuts')->first(),
        ], 201);
    });

    Route::get('/shortcuts', function (Request $request) {
        return response()->json([
            'status' => 201,
            'shortcuts' => $request->user()->shortcuts,
        ], 201);
    });

    Route::post('/shortcut', [ShortcutController::class, 'store']);
})->middleware('auth:sanctum');
