<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Categorie;
use App\Models\HistoriqueOperation;
use App\Models\Maintenance;
use App\Models\Ministere;
use App\Models\Service;
use App\Models\Transfert;

class DashboardController extends Controller
{
    public function index()
    {
        $statistiques = [
            'biens' => Bien::where('actif', true)->count(),
            'categories' => Categorie::where('actif', true)->count(),
            'ministeres' => Ministere::where('actif', true)->count(),
            'services' => Service::where('actif', true)->count(),
            'transferts_en_attente' => Transfert::where('statut', 'en_attente')->count(),
            'maintenances' => Maintenance::where('statut', 'signalee')->count(),
            'valeur_totale' => (float) Bien::where('actif', true)->sum('valeur_acquisition'),
        ];

        // Biens actifs par categorie (une seule requete)
        $categories = Categorie::where('actif', true)
            ->withCount(['biens as biens_count' => fn ($q) => $q->where('actif', true)])
            ->orderByDesc('biens_count')
            ->orderBy('nom')
            ->get();

        $parType = $categories->groupBy('type')->map->sum('biens_count');

        $operations = HistoriqueOperation::with(['utilisateur', 'bien'])
            ->latest()
            ->limit(6)
            ->get();

        return view('dashboard.index', compact('statistiques', 'categories', 'parType', 'operations'));
    }
}
