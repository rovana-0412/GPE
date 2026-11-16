<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcquisitionRequest;
use App\Models\Acquisition;
use App\Models\Bien;
use App\Services\AuditWriter;
use App\Services\MouvementNotificationClient;
use App\Services\ServiceWriteScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AcquisitionController extends Controller
{
    public function index(Request $request, ServiceWriteScope $scope): JsonResponse
    {
        $filters = $request->validate([
            'statut' => ['nullable', 'in:brouillon,en_attente,receptionnee,validee,rejetee'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $acquisitions = Acquisition::query()
            ->with(['categorie:id,nom', 'service:id,nom,ministere_id', 'bien:id,reference,designation'])
            ->when($scope->serviceIds($request) !== null, fn ($query) => $query->whereIn('service_id', $scope->serviceIds($request)))
            ->when(isset($filters['statut']), fn ($query) => $query->where('statut', $filters['statut']))
            ->when(isset($filters['service_id']), fn ($query) => $query->where('service_id', $filters['service_id']))
            ->when(isset($filters['q']), function ($query) use ($filters): void {
                $term = '%'.$filters['q'].'%';
                $query->where(fn ($search) => $search
                    ->where('reference', 'ilike', $term)
                    ->orWhere('designation', 'ilike', $term)
                    ->orWhere('fournisseur', 'ilike', $term)
                    ->orWhere('reference_facture', 'ilike', $term));
            })
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($acquisitions);
    }

    public function store(StoreAcquisitionRequest $request, AuditWriter $audit, ServiceWriteScope $scope): JsonResponse
    {
        $scope->authorize($request, (int) $request->validated('service_id'));

        $payload = $request->attributes->get('jwt_payload', []);
        $acquisition = DB::transaction(function () use ($request, $payload, $audit): Acquisition {
            $acquisition = Acquisition::create([
                ...$request->validated(),
                'demandeur_id' => (int) ($payload['sub'] ?? 0),
                'statut' => 'brouillon',
            ]);
            $audit->enregistrer('acquisition', (int) $acquisition->id, 'acquisition.cree', null, $acquisition->toArray());

            return $acquisition;
        });

        return response()->json(['data' => $acquisition->load(['categorie', 'service.ministere'])], Response::HTTP_CREATED);
    }

    public function show(Request $request, Acquisition $acquisition, ServiceWriteScope $scope): JsonResponse
    {
        $scope->authorize($request, (int) $acquisition->service_id);

        return response()->json([
            'data' => $acquisition->load(['categorie', 'service.ministere', 'bien']),
        ]);
    }

    public function transition(
        Request $request,
        Acquisition $acquisition,
        AuditWriter $audit,
        MouvementNotificationClient $notifications,
        ServiceWriteScope $scope,
    ): JsonResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,receptionnee,validee,rejetee'],
            'motif_rejet' => ['required_if:statut,rejetee', 'nullable', 'string', 'max:2000'],
        ]);
        $payload = $request->attributes->get('jwt_payload', []);
        $scope->authorize($request, (int) $acquisition->service_id);

        $acquisition = DB::transaction(function () use ($request, $scope, $acquisition, $data, $payload, $audit): Acquisition {
            $acquisition = Acquisition::query()->lockForUpdate()->findOrFail($acquisition->id);
            $scope->authorize($request, (int) $acquisition->service_id);
            $oldValue = $acquisition->toArray();
            $transitions = [
                'brouillon' => ['en_attente'],
                'en_attente' => ['receptionnee', 'rejetee'],
                'receptionnee' => ['validee', 'rejetee'],
                'validee' => [],
                'rejetee' => [],
            ];
            $nextStatus = $data['statut'];

            if (! in_array($nextStatus, $transitions[$acquisition->statut] ?? [], true)) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'La transition demandée est invalide.');
            }

            if ($nextStatus !== 'en_attente' && (int) $acquisition->demandeur_id === (int) ($payload['sub'] ?? 0)) {
                abort(Response::HTTP_FORBIDDEN, 'Le demandeur ne peut pas valider sa propre acquisition.');
            }

            if ($nextStatus === 'receptionnee') {
                $acquisition->receptionnee_le = now();
            }

            if ($nextStatus === 'rejetee') {
                $acquisition->motif_rejet = $data['motif_rejet'];
                $acquisition->validateur_id = (int) ($payload['sub'] ?? 0);
            }

            if ($nextStatus === 'validee') {
                $acquisition->bien()->associate(Bien::create([
                    'reference' => $acquisition->reference,
                    'designation' => $acquisition->designation,
                    'description' => $acquisition->description,
                    'categorie_id' => $acquisition->categorie_id,
                    'service_id' => $acquisition->service_id,
                    'numero_serie' => $acquisition->numero_serie,
                    'date_acquisition' => $acquisition->date_acquisition,
                    'valeur_acquisition' => $acquisition->cout,
                    'localisation' => $acquisition->localisation,
                    'etat' => 'bon_etat',
                    'statut' => 'disponible',
                    'actif' => true,
                ]));
                $acquisition->validee_le = now();
                $acquisition->validateur_id = (int) ($payload['sub'] ?? 0);
            }

            $acquisition->statut = $nextStatus;
            $acquisition->save();
            $audit->enregistrer(
                'acquisition',
                (int) $acquisition->id,
                'acquisition.statut_modifie',
                $oldValue,
                $acquisition->fresh()->toArray(),
                $acquisition->bien_id,
            );

            return $acquisition->fresh()->load(['categorie', 'service.ministere', 'bien']);
        });

        if (
            $acquisition->demandeur_id !== (int) ($payload['sub'] ?? 0)
            && in_array($acquisition->statut, ['receptionnee', 'validee', 'rejetee'], true)
        ) {
            $notifications->envoyer(
                (int) $acquisition->demandeur_id,
                'acquisition.'.$acquisition->statut,
                'Mise à jour de votre acquisition',
                'L’acquisition « '.$acquisition->reference.' » est maintenant « '.$acquisition->statut.' ».',
                ['acquisition_id' => $acquisition->id, 'bien_id' => $acquisition->bien_id, 'statut' => $acquisition->statut],
                (string) $request->bearerToken(),
            );
        }

        return response()->json(['data' => $acquisition]);
    }
}
