<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['status' => 'healthy', 'service' => 'auth']));

Route::post('/login', [AuthController::class, 'login']);

// Internal service endpoint (no auth required, for service-to-service calls)
Route::get('/users/names', [AuthController::class, 'userNames']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('scope:admin')->group(function () {
        Route::post('/register', [AuthController::class, 'register']); // Admin-only user creation
        Route::get('/users', [AuthController::class, 'users']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/verify', [AuditLogController::class, 'verify']);
    });
});