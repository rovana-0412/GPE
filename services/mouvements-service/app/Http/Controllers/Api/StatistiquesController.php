<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\HistoriqueOperation;
use App\Models\Maintenance;
use App\Models\Inventaire;
use App\Models\Transfert;
use App\Services\PerimetreScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatistiquesController extends Controller
{
    public function index(Request $request, PerimetreScope $scope): JsonResponse
    {
        $serviceIds = $scope->serviceIds($request);
        $bienIds = $scope->bienIds($request);

        $affectations = Affectation::query()->where('statut', 'active');
        $transferts = Transfert::query();
        $maintenances = Maintenance::query()->whereNotIn('statut', ['annulee']);
        $inventaires = Inventaire::query()->whereIn('statut', ['brouillon', 'en_cours', 'a_valider']);
        $historique = HistoriqueOperation::query();

        if ($serviceIds !== null) {
            $affectations->whereIn('service_id', $serviceIds);
            $transferts->where(fn ($q) => $q->whereIn('service_origine_id', $serviceIds)->orWhereIn('service_destination_id', $serviceIds));
            $inventaires->whereIn('service_id', $serviceIds);
            $maintenances->whereIn('bien_id', $bienIds ?? []);
            $historique->whereIn('bien_id', $bienIds ?? []);
        }

        return response()->json([
            'data' => [
                'affectations_actives' => (clone $affectations)->count(),
                'transferts_en_attente' => (clone $transferts)->where('statut', 'en_attente')->count(),
                'transferts_approuves' => (clone $transferts)->where('statut', 'approuvé')->count(),
                'maintenances_en_cours' => Maintenance::query()
                    ->whereIn('statut', ['signalee', 'planifiee', 'en_cours'])
                    ->when($serviceIds !== null, fn ($q) => $q->whereIn('bien_id', $bienIds ?? []))
                    ->count(),
                'inventaires_en_cours' => (clone $inventaires)->count(),
                'cout_total_maintenances' => (float) (clone $maintenances)->sum('cout'),
                'operations_recentes' => (clone $historique)->latest('id')->limit(10)->get(),
            ],
        ]);
    }
}
