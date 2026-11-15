<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Ministere;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class StatistiquesController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Categorie::query()
            ->select(['id', 'nom', 'type'])
            ->withCount('biens')
            ->orderBy('nom')
            ->get();

        $ministeres = Ministere::query()
            ->with(['services' => fn ($query) => $query->withCount('biens')])
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Ministere $ministere): array => [
                'id' => $ministere->id,
                'nom' => $ministere->nom,
                'biens_count' => $ministere->services->sum('biens_count'),
            ]);

        return response()->json([
            'data' => [
                'biens' => Bien::count(),
                'valeur_totale' => (float) Bien::sum('valeur_acquisition'),
                'categories' => Categorie::count(),
                'ministeres' => Ministere::count(),
                'services' => Service::count(),
                'biens_par_type' => $categories->groupBy('type')
                    ->map(fn ($items): int => (int) $items->sum('biens_count'))
                    ->all(),
                'biens_par_categorie' => $categories->map(fn (Categorie $categorie): array => [
                    'id' => $categorie->id,
                    'nom' => $categorie->nom,
                    'type' => $categorie->type,
                    'biens_count' => $categorie->biens_count,
                ]),
                'biens_par_ministere' => $ministeres,
            ],
        ]);
    }
}
