<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Ministere;
use App\Models\Service;
use App\Models\Transfert;
use App\Models\Maintenance;

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
        ];

        return view('dashboard.index', compact('statistiques'));
    }
}
