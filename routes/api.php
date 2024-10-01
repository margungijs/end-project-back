<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\VerifyEmailController;
use App\Http\Controllers\ImageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('/auth')->group(function (){
    Route::post('/register', [UserController::class, 'store']);

    Route::post('/login', [AuthController::class, 'store'])
        ->name('login');

    Route::post('/logout', [AuthController::class, 'destroy'])
        ->middleware('auth:sanctum');

//    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
//        ->middleware(['auth', 'signed'])->name('verification.verify');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/image', [ImageController::class, 'store'])
        ->middleware('auth:sanctum');
});


