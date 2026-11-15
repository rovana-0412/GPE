<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(MicroserviceClient $client)
    {
        $data = $client->request('patrimoine', 'GET', '/api/stats')->json('data', []);
        $statistiques = array_merge([
            'biens' => 0,
            'categories' => 0,
            'ministeres' => 0,
            'services' => 0,
            'valeur_totale' => 0,
            'transferts_en_attente' => 0,
            'maintenances' => 0,
        ], $data);
        $categories = collect($data['biens_par_categorie'] ?? [])
            ->map(fn (array $categorie): object => (object) $categorie);
        $parType = $data['biens_par_type'] ?? [];
        $ministeres = collect($data['biens_par_ministere'] ?? [])
            ->map(fn (array $ministere): object => (object) $ministere);
        $operations = new Collection;

        return view('dashboard.index', compact('statistiques', 'categories', 'parType', 'ministeres', 'operations'));
    }
}
