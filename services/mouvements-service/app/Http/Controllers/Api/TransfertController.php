<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transfert;
use App\Services\HistoriqueWriter;
use App\Services\NotificationWriter;
use App\Services\PatrimoineClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TransfertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'bien_id' => ['nullable', 'integer', 'min:1'],
            'bien_ids' => ['nullable', 'array', 'max:100'],
            'bien_ids.*' => ['integer', 'min:1'],
            'service_origine_id' => ['nullable', 'integer', 'min:1'],
            'service_destination_id' => ['nullable', 'integer', 'min:1'],
            'statut' => ['nullable', 'in:brouillon,en_attente,approuvé,effectué,rejeté'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $transferts = Transfert::query()
            ->when(isset($filters['bien_id']), fn ($query) => $query->where('bien_id', $filters['bien_id']))
            ->when(isset($filters['bien_ids']), fn ($query) => $query->whereIn('bien_id', $filters['bien_ids']))
            ->when(isset($filters['service_origine_id']), fn ($query) => $query->where('service_origine_id', $filters['service_origine_id']))
            ->when(isset($filters['service_destination_id']), fn ($query) => $query->where('service_destination_id', $filters['service_destination_id']))
            ->when(isset($filters['statut']), fn ($query) => $query->where('statut', $filters['statut']))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($transferts);
    }

    public function store(Request $request, PatrimoineClient $patrimoine, HistoriqueWriter $historique): JsonResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'service_destination_id' => ['required', 'integer', 'min:1'],
            'date_demande' => ['nullable', 'date'],
            'motif' => ['required', 'string', 'max:2000'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);

        $token = (string) $request->bearerToken();
        $bien = $patrimoine->bien($data['bien_id'], $token);

        if ($bien === null) {
            throw ValidationException::withMessages(['bien_id' => 'Le bien demandé n’existe pas.']);
        }

        $serviceOrigineId = filter_var($bien['service_id'], FILTER_VALIDATE_INT);

        if ($serviceOrigineId === false) {
            abort(Response::HTTP_BAD_GATEWAY, 'Le bien ne possède pas de service d’origine valide.');
        }

        if (! $patrimoine->serviceExiste($data['service_destination_id'], $token)) {
            throw ValidationException::withMessages(['service_destination_id' => 'Le service destinataire n’existe pas.']);
        }

        if ($serviceOrigineId === (int) $data['service_destination_id']) {
            throw ValidationException::withMessages(['service_destination_id' => 'Le service destinataire doit être différent du service d’origine.']);
        }

        $payload = $request->attributes->get('jwt_payload', []);
        $transfert = DB::transaction(function () use ($data, $serviceOrigineId, $payload, $historique): Transfert {
            $transfert = Transfert::create([
                'bien_id' => $data['bien_id'],
                'service_origine_id' => $serviceOrigineId,
                'service_destination_id' => $data['service_destination_id'],
                'demandeur_id' => (int) $payload['sub'],
                'date_demande' => $data['date_demande'] ?? now()->toDateString(),
                'motif' => $data['motif'],
                'observation' => $data['observation'] ?? null,
                'statut' => 'brouillon',
            ]);
            $historique->enregistrer('transfert.cree', $transfert->bien_id, null, $transfert->toArray());

            return $transfert;
        });

        return response()->json(['data' => $transfert], Response::HTTP_CREATED);
    }

    public function show(Transfert $transfert): JsonResponse
    {
        return response()->json(['data' => $transfert]);
    }

    public function transition(
        Request $request,
        Transfert $transfert,
        PatrimoineClient $patrimoine,
        HistoriqueWriter $historique,
        NotificationWriter $notifications,
    ): JsonResponse {
        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,approuvé,effectué,rejeté'],
            'motif_refus' => ['required_if:statut,rejeté', 'nullable', 'string', 'max:2000'],
        ]);

        $resultat = DB::transaction(function () use ($request, $transfert, $data, $patrimoine, $historique, $notifications): array {
            $transfert = Transfert::query()->lockForUpdate()->findOrFail($transfert->id);
            $ancienStatut = $transfert->statut;
            $transitions = [
                'brouillon' => ['en_attente'],
                'en_attente' => ['approuvé', 'rejeté'],
                'approuvé' => ['effectué'],
                'effectué' => [],
                'rejeté' => [],
            ];
            $nouveauStatut = $data['statut'];

            if (! in_array($nouveauStatut, $transitions[$ancienStatut] ?? [], true)) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, "Transition de statut invalide : {$ancienStatut} vers {$nouveauStatut}.");
            }

            $token = (string) $request->bearerToken();

            if ($patrimoine->bien($transfert->bien_id, $token) === null) {
                throw ValidationException::withMessages(['bien_id' => 'Le bien du transfert n’existe plus.']);
            }

            if ($nouveauStatut === 'effectué') {
                $patrimoine->changerService($transfert->bien_id, $transfert->service_destination_id, $token);
                $transfert->date_effective = now()->toDateString();
            }

            if (in_array($nouveauStatut, ['approuvé', 'rejeté'], true)) {
                $transfert->validateur_id = (int) $request->attributes->get('jwt_payload')['sub'];
                $transfert->date_validation = now()->toDateString();
            }

            if ($nouveauStatut === 'rejeté') {
                $transfert->motif_refus = $data['motif_refus'];
            }

            $transfert->statut = $nouveauStatut;
            $transfert->save();
            $historique->enregistrer(
                'transfert.statut_modifie',
                $transfert->bien_id,
                ['statut' => $ancienStatut],
                ['statut' => $nouveauStatut, 'transfert_id' => $transfert->id],
            );

            $actorId = (int) $request->attributes->get('jwt_payload')['sub'];
            if ($transfert->demandeur_id !== $actorId && in_array($nouveauStatut, ['approuvé', 'rejeté', 'effectué'], true)) {
                $notifications->envoyer(
                    (int) $transfert->demandeur_id,
                    'transfert.'.$nouveauStatut,
                    'Mise à jour de votre transfert',
                    'Le transfert #'.$transfert->id.' est maintenant « '.$nouveauStatut.' ».',
                    ['transfert_id' => $transfert->id, 'bien_id' => $transfert->bien_id, 'statut' => $nouveauStatut],
                );
            }

            return ['data' => $transfert->fresh(), 'ancien_statut' => $ancienStatut];
        });

        return response()->json(['data' => $resultat['data']]);
    }
}
