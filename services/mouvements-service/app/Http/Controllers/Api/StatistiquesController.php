<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\HistoriqueOperation;
use App\Models\Maintenance;
use App\Models\Inventaire;
use App\Models\Transfert;
use Illuminate\Http\JsonResponse;

class StatistiquesController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'affectations_actives' => Affectation::where('statut', 'active')->count(),
                'transferts_en_attente' => Transfert::where('statut', 'en_attente')->count(),
                'transferts_approuves' => Transfert::where('statut', 'approuvé')->count(),
                'maintenances_en_cours' => Maintenance::whereIn('statut', ['signalee', 'planifiee', 'en_cours'])->count(),
                'inventaires_en_cours' => Inventaire::whereIn('statut', ['brouillon', 'en_cours', 'a_valider'])->count(),
                'cout_total_maintenances' => (float) Maintenance::whereNotIn('statut', ['annulee'])->sum('cout'),
                'operations_recentes' => HistoriqueOperation::query()->latest('id')->limit(10)->get(),
            ],
        ]);
    }
}
