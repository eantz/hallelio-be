<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\UploadController;
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
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'delete']);
});

Route::prefix('member')->middleware('auth:sanctum')->group(function () {
    Route::get('/list', [MemberController::class, 'list']);
    Route::get('/{id}', [MemberController::class, 'detail']);
    Route::post('/', [MemberController::class, 'create']);
    Route::put('/{id}', [MemberController::class, 'update']);
    Route::delete('/{id}', [MemberController::class, 'delete']);
    Route::post('/regenerate-qr-code/{id}', [MemberController::class, 'regenerateQRCode']);
});

Route::prefix('event')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [EventController::class, 'create']);
    Route::put('/{eventID}', [EventController::class, 'update']);
});

Route::post('/upload', [UploadController::class, 'upload']);
