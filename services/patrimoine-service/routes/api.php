<?php

use App\Http\Controllers\Api\BienController;
use App\Http\Controllers\Api\CategorieController;
use App\Http\Controllers\Api\MinistereController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\StatistiquesController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.auth')->group(function (): void {
    Route::apiResource('biens', BienController::class);
    Route::apiResource('categories', CategorieController::class)
        ->parameters(['categories' => 'categorie']);
    Route::apiResource('ministeres', MinistereController::class);
    Route::apiResource('services', ServiceController::class);
    Route::get('stats', [StatistiquesController::class, 'index']);
});
