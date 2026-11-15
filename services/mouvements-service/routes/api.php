<?php

use App\Http\Controllers\Api\AffectationController;
use App\Http\Controllers\Api\HistoriqueOperationController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\StatistiquesController;
use App\Http\Controllers\Api\TransfertController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.auth')->group(function (): void {
    Route::apiResource('affectations', AffectationController::class)->except('destroy');
    Route::apiResource('transferts', TransfertController::class)->except(['destroy', 'update']);
    Route::patch('transferts/{transfert}/statut', [TransfertController::class, 'transition']);
    Route::apiResource('maintenances', MaintenanceController::class)->except('destroy');
    Route::get('historique', [HistoriqueOperationController::class, 'index']);
    Route::get('stats', [StatistiquesController::class, 'index']);
});
