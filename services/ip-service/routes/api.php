<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\IpAddressController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.token')->group(function () {
    Route::get('/ip-addresses', [IpAddressController::class, 'index']);
    Route::post('/ip-addresses', [IpAddressController::class, 'store']);
    Route::get('/ip-addresses/{id}', [IpAddressController::class, 'show']);
    Route::put('/ip-addresses/{id}', [IpAddressController::class, 'update']);
    Route::delete('/ip-addresses/{id}', [IpAddressController::class, 'destroy']);

    // Admin only routes
    Route::middleware('auth.token:admin')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/verify', [AuditLogController::class, 'verify']);
    });
});