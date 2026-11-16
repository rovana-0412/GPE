<?php

use App\Http\Controllers\Api\AffectationController;
use App\Http\Controllers\Api\HistoriqueOperationController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\StatistiquesController;
use App\Http\Controllers\Api\TransfertController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.auth')->group(function (): void {
    // Routes en lecture seule (tous les rôles authentifiés)
    Route::get('affectations', [AffectationController::class, 'index']);
    Route::get('affectations/{affectation}', [AffectationController::class, 'show']);
    Route::get('transferts', [TransfertController::class, 'index']);
    Route::get('transferts/{transfert}', [TransfertController::class, 'show']);
    Route::get('maintenances', [MaintenanceController::class, 'index']);
    Route::get('maintenances/{maintenance}', [MaintenanceController::class, 'show']);
    Route::get('historique', [HistoriqueOperationController::class, 'index']);
    Route::get('stats', [StatistiquesController::class, 'index']);

    // Routes d'écriture (nécessitent rôle gestionnaire ou admin)
    Route::middleware('role:gestionnaire-patrimoine')->group(function (): void {
        Route::post('affectations', [AffectationController::class, 'store']);
        Route::put('affectations/{affectation}', [AffectationController::class, 'update']);

        Route::post('transferts', [TransfertController::class, 'store']);
        Route::patch('transferts/{transfert}/statut', [TransfertController::class, 'transition']);

        Route::post('maintenances', [MaintenanceController::class, 'store']);
        Route::put('maintenances/{maintenance}', [MaintenanceController::class, 'update']);
    });
});
