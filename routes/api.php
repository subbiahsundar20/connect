<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserDirectoryController;
use App\Http\Controllers\Api\PostController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/users/{user}', [ProfileController::class, 'publicProfile']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto',]);
    Route::get('/users', [UserDirectoryController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);

    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
});
