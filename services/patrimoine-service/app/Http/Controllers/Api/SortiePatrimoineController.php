<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bien;
use App\Models\SortiePatrimoine;
use App\Services\AuditWriter;
use App\Services\MouvementNotificationClient;
use App\Services\ServiceWriteScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SortiePatrimoineController extends Controller
{
    public function index(Request $request, ServiceWriteScope $scope): JsonResponse
    {
        $filters = $request->validate([
            'bien_id' => ['nullable', 'integer', 'min:1'],
            'statut' => ['nullable', 'in:demandee,approuvee,rejetee,executee'],
            'type_sortie' => ['nullable', 'in:cession,restitution,mise_au_rebut,declassement,autre'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sorties = SortiePatrimoine::query()
            ->with('bien:id,reference,designation,service_id,actif,statut')
            ->when($scope->serviceIds($request) !== null, fn ($query) => $query->whereHas('bien', fn ($bien) => $bien->whereIn('service_id', $scope->serviceIds($request))))
            ->when(isset($filters['bien_id']), fn ($query) => $query->where('bien_id', $filters['bien_id']))
            ->when(isset($filters['statut']), fn ($query) => $query->where('statut', $filters['statut']))
            ->when(isset($filters['type_sortie']), fn ($query) => $query->where('type_sortie', $filters['type_sortie']))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($sorties);
    }

    public function store(Request $request, AuditWriter $audit, ServiceWriteScope $scope): JsonResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'exists:biens,id'],
            'type_sortie' => ['required', 'in:cession,restitution,mise_au_rebut,declassement,autre'],
            'motif' => ['required', 'string', 'max:5000'],
            'date_demande' => ['nullable', 'date'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);
        $payload = $request->attributes->get('jwt_payload', []);

        $scope->authorize($request, (int) Bien::query()->whereKey($data['bien_id'])->value('service_id'));

        $sortie = DB::transaction(function () use ($request, $scope, $data, $payload, $audit): SortiePatrimoine {
            $bien = Bien::query()->lockForUpdate()->findOrFail($data['bien_id']);
            $scope->authorize($request, (int) $bien->service_id);

            if (! $bien->actif || $bien->trashed()) {
                abort(Response::HTTP_CONFLICT, 'Ce bien n’est pas actif dans le registre.');
            }

            if (SortiePatrimoine::query()
                ->where('bien_id', $bien->id)
                ->whereIn('statut', ['demandee', 'approuvee'])
                ->exists()) {
                abort(Response::HTTP_CONFLICT, 'Une demande de sortie est déjà en cours pour ce bien.');
            }

            $sortie = SortiePatrimoine::create([
                ...$data,
                'statut' => 'demandee',
                'demandeur_id' => (int) ($payload['sub'] ?? 0),
                'date_demande' => $data['date_demande'] ?? now()->toDateString(),
            ]);
            $audit->enregistrer('sortie', (int) $sortie->id, 'sortie.demandee', null, $sortie->toArray(), (int) $bien->id);

            return $sortie;
        });

        return response()->json(['data' => $sortie->load('bien')], Response::HTTP_CREATED);
    }

    public function transition(
        Request $request,
        SortiePatrimoine $sortie,
        AuditWriter $audit,
        MouvementNotificationClient $notifications,
        ServiceWriteScope $scope,
    ): JsonResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:approuvee,rejetee,executee'],
            'motif_rejet' => ['required_if:statut,rejetee', 'nullable', 'string', 'max:2000'],
        ]);
        $payload = $request->attributes->get('jwt_payload', []);
        $scope->authorize($request, (int) $sortie->bien()->value('service_id'));

        $sortie = DB::transaction(function () use ($request, $scope, $sortie, $data, $payload, $audit): SortiePatrimoine {
            $sortie = SortiePatrimoine::query()->lockForUpdate()->findOrFail($sortie->id);
            $scope->authorize($request, (int) $sortie->bien()->value('service_id'));
            $oldValue = $sortie->toArray();
            $transitions = [
                'demandee' => ['approuvee', 'rejetee'],
                'approuvee' => ['executee'],
                'rejetee' => [],
                'executee' => [],
            ];
            $nextStatus = $data['statut'];

            if (! in_array($nextStatus, $transitions[$sortie->statut] ?? [], true)) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'La transition demandée est invalide.');
            }

            if (in_array($nextStatus, ['approuvee', 'rejetee'], true)
                && (int) $sortie->demandeur_id === (int) ($payload['sub'] ?? 0)) {
                abort(Response::HTTP_FORBIDDEN, 'Le demandeur ne peut pas approuver ou rejeter sa propre demande.');
            }

            if ($nextStatus === 'executee') {
                $bien = Bien::query()->lockForUpdate()->findOrFail($sortie->bien_id);

                if (! $bien->actif || $bien->trashed()) {
                    abort(Response::HTTP_CONFLICT, 'Le bien n’est plus actif dans le registre.');
                }

                $bien->update(['actif' => false, 'statut' => 'sorti']);
                $sortie->date_execution = now()->toDateString();
            }

            if (in_array($nextStatus, ['approuvee', 'rejetee'], true)) {
                $sortie->validateur_id = (int) ($payload['sub'] ?? 0);
            }

            if ($nextStatus === 'rejetee') {
                $sortie->motif_rejet = $data['motif_rejet'];
            }

            $sortie->statut = $nextStatus;
            $sortie->save();
            $audit->enregistrer(
                'sortie',
                (int) $sortie->id,
                'sortie.statut_modifie',
                $oldValue,
                $sortie->fresh()->toArray(),
                (int) $sortie->bien_id,
            );

            return $sortie->fresh()->load('bien');
        });

        if ($sortie->demandeur_id !== (int) ($payload['sub'] ?? 0)) {
            $notifications->envoyer(
                (int) $sortie->demandeur_id,
                'sortie.'.$sortie->statut,
                'Mise à jour de votre demande de sortie',
                'La demande de sortie #'.$sortie->id.' est maintenant « '.$sortie->statut.' ».',
                ['sortie_id' => $sortie->id, 'bien_id' => $sortie->bien_id, 'statut' => $sortie->statut],
                (string) $request->bearerToken(),
            );
        }

        return response()->json(['data' => $sortie]);
    }
}
