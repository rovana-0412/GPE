<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(MicroserviceClient $client, Request $request)
    {
        $data = $client->request('patrimoine', 'GET', '/api/stats')->json('data', []);
        $mouvements = $client->request('mouvements', 'GET', '/api/stats')->json('data', []);
        $request->attributes->set('mouvements_stats', $mouvements);
        $statistiques = array_merge([
            'biens' => 0,
            'categories' => 0,
            'ministeres' => 0,
            'services' => 0,
            'valeur_totale' => 0,
            'maintenances' => 0,
        ], $data);
        $statistiques['transferts_en_attente'] = (int) ($mouvements['transferts_en_attente'] ?? 0);
        $statistiques['maintenances'] = (int) ($mouvements['maintenances_en_cours'] ?? 0);
        $categories = collect($data['biens_par_categorie'] ?? [])
            ->map(fn (array $categorie): object => (object) $categorie);
        $parType = $data['biens_par_type'] ?? [];
        $ministeres = collect($data['biens_par_ministere'] ?? [])
            ->map(fn (array $ministere): object => (object) $ministere);
        $operations = collect($mouvements['operations_recentes'] ?? [])
            ->map(function (array $operation): object {
                $operation['created_at'] = Carbon::parse($operation['created_at']);

                return (object) $operation;
            });

        return view('dashboard.index', compact('statistiques', 'categories', 'parType', 'ministeres', 'operations'));
    }
}
