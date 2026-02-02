<?php

use App\Http\Controllers\Api\GatewayController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [GatewayController::class, 'health']);

Route::middleware('throttle:60,1')->group(function () {
    // Auth routes
    Route::post('/auth/register', [GatewayController::class, 'register']);
    Route::post('/auth/login', [GatewayController::class, 'login']);
    Route::post('/auth/logout', [GatewayController::class, 'logout']);
    Route::get('/auth/user', [GatewayController::class, 'user']);
    Route::post('/auth/refresh', [GatewayController::class, 'refresh']);
    Route::get('/auth/users', [GatewayController::class, 'users']);

    // IP Address routes
    Route::get('/ip-addresses', [GatewayController::class, 'ipAddressIndex']);
    Route::post('/ip-addresses', [GatewayController::class, 'ipAddressStore']);
    Route::get('/ip-addresses/{id}', [GatewayController::class, 'ipAddressShow']);
    Route::put('/ip-addresses/{id}', [GatewayController::class, 'ipAddressUpdate']);
    Route::delete('/ip-addresses/{id}', [GatewayController::class, 'ipAddressDestroy']);

    // Audit Log routes
    Route::get('/audit-logs', [GatewayController::class, 'auditLogIndex']);
    Route::get('/audit-logs/verify', [GatewayController::class, 'auditLogVerify']);
});