<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AcquisitionController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InventaireController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\MouvementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SortieController;
use App\Http\Controllers\PlatformSettingsController;
use App\Http\Controllers\UserController;
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
    Route::get('/recherche', [GlobalSearchController::class, 'index'])->name('recherche.index');
    Route::get('/rapports', [ReportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/export/pdf', [ReportController::class, 'pdf'])->name('rapports.pdf');
    Route::get('/rapports/export/excel', [ReportController::class, 'excel'])->name('rapports.excel');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/lue', [NotificationController::class, 'markAsRead'])
        ->whereNumber('id')->name('notifications.read');
    Route::get('/inventaires', [InventaireController::class, 'index'])->name('inventaires.index');
    Route::get('/inventaires/creer', [InventaireController::class, 'create'])->name('inventaires.create');
    Route::post('/inventaires', [InventaireController::class, 'store'])->name('inventaires.store');
    Route::get('/inventaires/{id}', [InventaireController::class, 'show'])->whereNumber('id')->name('inventaires.show');
    Route::post('/inventaires/{id}/lignes', [InventaireController::class, 'addLine'])->whereNumber('id')->name('inventaires.lines.store');
    Route::patch('/inventaires/{id}/lignes/{lineId}', [InventaireController::class, 'updateLine'])
        ->whereNumber(['id', 'lineId'])->name('inventaires.lines.update');
    Route::patch('/inventaires/{id}/statut', [InventaireController::class, 'transition'])->whereNumber('id')->name('inventaires.status');

    Route::get('/sorties', [SortieController::class, 'index'])->name('sorties.index');
    Route::get('/sorties/creer', [SortieController::class, 'create'])->name('sorties.create');
    Route::post('/sorties', [SortieController::class, 'store'])->name('sorties.store');
    Route::patch('/sorties/{id}/statut', [SortieController::class, 'transition'])
        ->whereNumber('id')->name('sorties.status');

    Route::get('/acquisitions', [AcquisitionController::class, 'index'])->name('acquisitions.index');
    Route::get('/acquisitions/creer', [AcquisitionController::class, 'create'])->name('acquisitions.create');
    Route::post('/acquisitions', [AcquisitionController::class, 'store'])->name('acquisitions.store');
    Route::patch('/acquisitions/{id}/statut', [AcquisitionController::class, 'transition'])
        ->whereNumber('id')->name('acquisitions.status');

    Route::middleware('role:administrateur-general')->prefix('administration')->group(function (): void {
        Route::get('/parametres', [PlatformSettingsController::class, 'index'])->name('administration.parametres');
        Route::put('/parametres', [PlatformSettingsController::class, 'update'])->name('administration.parametres.update');
        Route::get('/utilisateurs', [UserController::class, 'index'])->name('utilisateurs.index');
        Route::get('/utilisateurs/creer', [UserController::class, 'create'])->name('utilisateurs.create');
        Route::post('/utilisateurs', [UserController::class, 'store'])->name('utilisateurs.store');
        Route::get('/utilisateurs/{id}/modifier', [UserController::class, 'edit'])
            ->whereNumber('id')->name('utilisateurs.edit');
        Route::put('/utilisateurs/{id}', [UserController::class, 'update'])
            ->whereNumber('id')->name('utilisateurs.update');
        Route::patch('/utilisateurs/{id}/statut', [UserController::class, 'changeStatus'])
            ->whereNumber('id')->name('utilisateurs.status');
    });

    Route::get('/documents/{cibleType}/{cibleId}', [DocumentController::class, 'index'])
        ->whereIn('cibleType', ['bien', 'acquisition', 'affectation', 'transfert', 'maintenance'])
        ->whereNumber('cibleId')->name('documents.index');
    Route::post('/documents/{cibleType}/{cibleId}', [DocumentController::class, 'store'])
        ->whereIn('cibleType', ['bien', 'acquisition', 'affectation', 'transfert', 'maintenance'])
        ->whereNumber('cibleId')->name('documents.store');
    Route::get('/documents/{cibleType}/{cibleId}/{documentId}/telecharger', [DocumentController::class, 'download'])
        ->whereIn('cibleType', ['bien', 'acquisition', 'affectation', 'transfert', 'maintenance'])
        ->whereNumber(['cibleId', 'documentId'])->name('documents.download');
    Route::delete('/documents/{cibleType}/{cibleId}/{documentId}', [DocumentController::class, 'destroy'])
        ->whereIn('cibleType', ['bien', 'acquisition', 'affectation', 'transfert', 'maintenance'])
        ->whereNumber(['cibleId', 'documentId'])->name('documents.destroy');

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
