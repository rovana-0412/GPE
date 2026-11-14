<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        // TODO: Remplacer par des appels réels aux microservices
        // Pour l'instant, on utilise Http::fake() pour simuler les réponses

        // Simuler les statistiques depuis patrimoine-service
        $statistiques = [
            'biens' => 150,
            'categories' => 12,
            'ministeres' => 8,
            'services' => 25,
            'transferts_en_attente' => 5,
            'maintenances' => 3,
            'valeur_totale' => 25000000.00,
        ];

        // Simuler les catégories depuis patrimoine-service
        $categories = collect([
            (object) ['id' => 1, 'nom' => 'Véhicules', 'type' => 'mobilier', 'biens_count' => 45],
            (object) ['id' => 2, 'nom' => 'Bâtiments', 'type' => 'immobilier', 'biens_count' => 30],
            (object) ['id' => 3, 'nom' => 'Équipements informatiques', 'type' => 'mobilier', 'biens_count' => 35],
            (object) ['id' => 4, 'nom' => 'Mobilier de bureau', 'type' => 'mobilier', 'biens_count' => 40],
        ]);

        $parType = $categories->groupBy('type')->map->sum('biens_count');

        // Simuler les opérations depuis mouvements-service
        $operations = collect([
            (object) [
                'id' => 1,
                'action' => 'affectation.creee',
                'created_at' => now()->subDays(1),
                'utilisateur' => (object) ['name' => 'Jean Kouassi'],
                'bien' => (object) ['designation' => 'Toyota Hilux'],
            ],
            (object) [
                'id' => 2,
                'action' => 'transfert.demande',
                'created_at' => now()->subDays(2),
                'utilisateur' => (object) ['name' => 'Marie Tchao'],
                'bien' => (object) ['designation' => 'Imprimante HP'],
            ],
        ]);

        return view('dashboard.index', compact('statistiques', 'categories', 'parType', 'operations'));
    }
}
