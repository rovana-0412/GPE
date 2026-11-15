<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MouvementController;
use App\Http\Controllers\ReferenceController;
use Illuminate\Support\Facades\Route;

// Page d'accueil
Route::get('/', function () {
    return redirect()->route(session('jwt_token') ? 'dashboard' : 'login');
})->name('accueil');

// Authentification
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/connexion', [AuthController::class, 'login'])
        ->name('login.store');
});

// Déconnexion
Route::post('/deconnexion', [AuthController::class, 'logout'])
    ->middleware('jwt.auth')
    ->name('logout');

// Tableau de bord : accès réservé aux utilisateurs connectés
Route::get('/tableau-de-bord', [DashboardController::class, 'index'])
    ->middleware('jwt.auth')
    ->name('dashboard');

Route::middleware('jwt.auth')->group(function (): void {
    Route::get('/biens', [BienController::class, 'index'])->name('biens.index');
    Route::get('/biens/creer', [BienController::class, 'create'])->name('biens.create');
    Route::post('/biens', [BienController::class, 'store'])->name('biens.store');
    Route::get('/biens/{id}/modifier', [BienController::class, 'edit'])->whereNumber('id')->name('biens.edit');
    Route::put('/biens/{id}', [BienController::class, 'update'])->whereNumber('id')->name('biens.update');
    Route::delete('/biens/{id}', [BienController::class, 'destroy'])->whereNumber('id')->name('biens.destroy');
    Route::get('/biens/{id}', [BienController::class, 'show'])->whereNumber('id')->name('biens.show');

    foreach (['categories', 'ministeres', 'services'] as $resource) {
        Route::get('/'.$resource, [ReferenceController::class, 'index'])
            ->defaults('resource', $resource)->name($resource.'.index');
        Route::get('/'.$resource.'/creer', [ReferenceController::class, 'create'])
            ->defaults('resource', $resource)->name($resource.'.create');
        Route::post('/'.$resource, [ReferenceController::class, 'store'])
            ->defaults('resource', $resource)->name($resource.'.store');
        Route::get('/'.$resource.'/{id}/modifier', [ReferenceController::class, 'edit'])
            ->whereNumber('id')->defaults('resource', $resource)->name($resource.'.edit');
        Route::put('/'.$resource.'/{id}', [ReferenceController::class, 'update'])
            ->whereNumber('id')->defaults('resource', $resource)->name($resource.'.update');
        Route::delete('/'.$resource.'/{id}', [ReferenceController::class, 'destroy'])
            ->whereNumber('id')->defaults('resource', $resource)->name($resource.'.destroy');
    }

    Route::get('/affectations', [MouvementController::class, 'affectations'])->name('affectations.index');
    Route::post('/affectations', [MouvementController::class, 'creerAffectation'])->name('affectations.store');
    Route::patch('/affectations/{id}', [MouvementController::class, 'modifierAffectation'])
        ->whereNumber('id')->name('affectations.update');

    Route::get('/transferts', [MouvementController::class, 'transferts'])->name('transferts.index');
    Route::post('/transferts', [MouvementController::class, 'creerTransfert'])->name('transferts.store');
    Route::patch('/transferts/{id}/statut', [MouvementController::class, 'changerStatutTransfert'])
        ->whereNumber('id')->name('transferts.statut');

    Route::get('/maintenances', [MouvementController::class, 'maintenances'])->name('maintenances.index');
    Route::post('/maintenances', [MouvementController::class, 'creerMaintenance'])->name('maintenances.store');
    Route::patch('/maintenances/{id}', [MouvementController::class, 'modifierMaintenance'])
        ->whereNumber('id')->name('maintenances.update');

    Route::get('/historique', [MouvementController::class, 'historique'])->name('historique.index');
});
