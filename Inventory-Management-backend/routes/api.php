<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;


Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('register', [App\Http\Controllers\AuthController::class, 'register']);
    Route::post('login', [App\Http\Controllers\AuthController::class, 'login']);
    Route::post('verify-2fa', [App\Http\Controllers\AuthController::class, 'verify2FA']);
    Route::post('confirm-2fa', [App\Http\Controllers\AuthController::class, 'confirm2FA']);

    
    Route::group([
        'middleware' => 'auth:api'
    ], function() {
        Route::post('logout', [App\Http\Controllers\AuthController::class, 'logout']);
        Route::post('enable-2fa', [App\Http\Controllers\AuthController::class, 'enable2FA']);
        Route::post('disable-2fa', [App\Http\Controllers\AuthController::class, 'disable2FA']);
        Route::get('user-profile', [App\Http\Controllers\AuthController::class, 'userProfile']);
    });
});

Route::group([
    'prefix' => 'users',
    'middleware' => 'auth:api'
], function() {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});


Route::group([
    'prefix' => 'inventory',
    'middleware' => 'auth:api'
], function() {
    Route::get('/', [App\Http\Controllers\InventoryController::class, 'index']);
    Route::post('/', [App\Http\Controllers\InventoryController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\InventoryController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\InventoryController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\InventoryController::class, 'destroy']);
});

