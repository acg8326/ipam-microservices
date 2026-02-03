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
    Route::get('/auth/audit-logs', [GatewayController::class, 'authAuditLogIndex']);
    Route::get('/auth/audit-logs/verify', [GatewayController::class, 'authAuditLogVerify']);

    // Dashboard
    Route::get('/dashboard/stats', [GatewayController::class, 'dashboardStats']);

    // Subnet routes
    Route::get('/subnets', [GatewayController::class, 'subnetIndex']);
    Route::post('/subnets', [GatewayController::class, 'subnetStore']);
    Route::get('/subnets/tree', [GatewayController::class, 'subnetTree']);
    Route::get('/subnets/{id}', [GatewayController::class, 'subnetShow']);
    Route::put('/subnets/{id}', [GatewayController::class, 'subnetUpdate']);
    Route::delete('/subnets/{id}', [GatewayController::class, 'subnetDestroy']);
    Route::get('/subnets/{id}/ip-addresses', [GatewayController::class, 'subnetIpAddresses']);

    // IP Address routes
    Route::get('/ip-addresses', [GatewayController::class, 'ipAddressIndex']);
    Route::post('/ip-addresses', [GatewayController::class, 'ipAddressStore']);
    Route::get('/ip-addresses/{id}', [GatewayController::class, 'ipAddressShow']);
    Route::put('/ip-addresses/{id}', [GatewayController::class, 'ipAddressUpdate']);
    Route::delete('/ip-addresses/{id}', [GatewayController::class, 'ipAddressDestroy']);

    // IP Service Audit Log routes
    Route::get('/audit-logs', [GatewayController::class, 'auditLogIndex']);
    Route::get('/audit-logs/verify', [GatewayController::class, 'auditLogVerify']);
});