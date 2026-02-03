<?php

use App\Http\Controllers\Api\GatewayController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [GatewayController::class, 'health']);

// Auth user endpoint - higher rate limit (used on every page load)
Route::middleware('throttle:300,1')->group(function () {
    Route::get('/auth/user', [GatewayController::class, 'user']);
    Route::post('/auth/refresh', [GatewayController::class, 'refresh']);
});

// Read-only endpoints - moderate rate limit
Route::middleware('throttle:120,1')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [GatewayController::class, 'dashboardStats']);

    // IP Address read routes
    Route::get('/ip-addresses', [GatewayController::class, 'ipAddressIndex']);
    Route::get('/ip-addresses/{id}', [GatewayController::class, 'ipAddressShow']);

    // Audit log routes
    Route::get('/audit-logs', [GatewayController::class, 'auditLogIndex']);
    Route::get('/audit-logs/verify', [GatewayController::class, 'auditLogVerify']);
    Route::get('/auth/audit-logs', [GatewayController::class, 'authAuditLogIndex']);
    Route::get('/auth/audit-logs/verify', [GatewayController::class, 'authAuditLogVerify']);
    Route::get('/auth/users', [GatewayController::class, 'users']);
});

// Write endpoints - stricter rate limit to prevent abuse
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/auth/register', [GatewayController::class, 'register']);
    Route::post('/auth/login', [GatewayController::class, 'login']);
    Route::post('/auth/logout', [GatewayController::class, 'logout']);

    // IP Address write routes
    Route::post('/ip-addresses', [GatewayController::class, 'ipAddressStore']);
    Route::put('/ip-addresses/{id}', [GatewayController::class, 'ipAddressUpdate']);
    Route::delete('/ip-addresses/{id}', [GatewayController::class, 'ipAddressDestroy']);
});