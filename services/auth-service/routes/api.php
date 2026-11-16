<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\PlatformSettingController;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/me', [AuthController::class, 'me'])->middleware('jwt.auth');
Route::get('/settings', [PlatformSettingController::class, 'index'])->middleware('jwt.auth');
Route::get('/audit', [AuditController::class, 'index'])
    ->middleware(['jwt.auth', 'role:administrateur-general,auditeur']);

Route::prefix('admin')
    ->middleware(['jwt.auth', 'role:administrateur-general'])
    ->group(function (): void {
        Route::get('/roles', [UserController::class, 'roles']);
        Route::get('/settings', [PlatformSettingController::class, 'index']);
        Route::put('/settings', [PlatformSettingController::class, 'update']);
        Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update']);
        Route::patch('/users/{user}/status', [UserController::class, 'changeStatus']);
    });
