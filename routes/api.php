<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('/list', [UserController::class, 'list']);
    Route::get('/active-user', [UserController::class, 'activeUser']);
    Route::get('/{id}', [UserController::class, 'detail']);
    Route::post('/', [UserController::class, 'create']);
    Route::put('/{id}', [UserController::class, 'edit']);
    Route::delete('/{id}', [UserController::class, 'delete']);
});
