
<?php
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;

// Page d'accueil
Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
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
    ->middleware('auth')
    ->name('logout');

    // Tableau de bord : accès réservé aux utilisateurs connectés
Route::get('/tableau-de-bord', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');
