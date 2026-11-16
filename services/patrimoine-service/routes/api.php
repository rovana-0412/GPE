<?php

use App\Http\Controllers\Api\BienController;
use App\Http\Controllers\Api\AcquisitionController;
use App\Http\Controllers\Api\SortiePatrimoineController;
use App\Http\Controllers\Api\CategorieController;
use App\Http\Controllers\Api\MinistereController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\StatistiquesController;
use App\Http\Controllers\Api\AuditController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.auth')->group(function (): void {
    // Routes en lecture seule (tous les rôles authentifiés)
    Route::get('biens', [BienController::class, 'index']);
    Route::get('biens/{bien}', [BienController::class, 'show']);
    Route::get('acquisitions', [AcquisitionController::class, 'index']);
    Route::get('acquisitions/{acquisition}', [AcquisitionController::class, 'show']);
    Route::get('sorties', [SortiePatrimoineController::class, 'index']);
    Route::get('categories', [CategorieController::class, 'index']);
    Route::get('categories/{categorie}', [CategorieController::class, 'show']);
    Route::get('ministeres', [MinistereController::class, 'index']);
    Route::get('ministeres/{ministere}', [MinistereController::class, 'show']);
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);
    Route::get('stats', [StatistiquesController::class, 'index']);
    Route::get('audit', [AuditController::class, 'index'])
        ->middleware('role:administrateur-general,auditeur');

    Route::middleware('role:administrateur-general,gestionnaire-patrimoine,responsable-ministeriel,agent-gestionnaire')->group(function (): void {
        Route::post('biens', [BienController::class, 'store']);
        Route::put('biens/{bien}', [BienController::class, 'update']);
        Route::delete('biens/{bien}', [BienController::class, 'destroy']);
        Route::post('acquisitions', [AcquisitionController::class, 'store']);
        Route::patch('acquisitions/{acquisition}/statut', [AcquisitionController::class, 'transition']);
        Route::post('sorties', [SortiePatrimoineController::class, 'store']);
        Route::patch('sorties/{sortie}/statut', [SortiePatrimoineController::class, 'transition']);
    });

    // Routes d'écriture (nécessitent rôle gestionnaire ou admin)
    Route::middleware('role:gestionnaire-patrimoine')->group(function (): void {
        Route::post('biens', [BienController::class, 'store']);
        Route::put('biens/{bien}', [BienController::class, 'update']);
        Route::delete('biens/{bien}', [BienController::class, 'destroy']);
        Route::post('acquisitions', [AcquisitionController::class, 'store']);
        Route::patch('acquisitions/{acquisition}/statut', [AcquisitionController::class, 'transition']);
        Route::post('sorties', [SortiePatrimoineController::class, 'store']);
        Route::patch('sorties/{sortie}/statut', [SortiePatrimoineController::class, 'transition']);

        Route::post('categories', [CategorieController::class, 'store']);
        Route::put('categories/{categorie}', [CategorieController::class, 'update']);
        Route::delete('categories/{categorie}', [CategorieController::class, 'destroy']);

        Route::post('ministeres', [MinistereController::class, 'store']);
        Route::put('ministeres/{ministere}', [MinistereController::class, 'update']);
        Route::delete('ministeres/{ministere}', [MinistereController::class, 'destroy']);

        Route::post('services', [ServiceController::class, 'store']);
        Route::put('services/{service}', [ServiceController::class, 'update']);
        Route::delete('services/{service}', [ServiceController::class, 'destroy']);
    });
});
