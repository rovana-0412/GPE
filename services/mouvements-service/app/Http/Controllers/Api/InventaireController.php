<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventaire;
use App\Models\InventaireLigne;
use App\Services\HistoriqueWriter;
use App\Services\NotificationWriter;
use App\Services\PatrimoineClient;
use App\Services\PerimetreScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class InventaireController extends Controller
{
    public function index(Request $request, PerimetreScope $scope): JsonResponse
    {
        $filters = $request->validate([
            'service_id' => ['nullable', 'integer', 'min:1'],
            'statut' => ['nullable', 'in:brouillon,en_cours,a_valider,valide'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $inventaires = $scope
            ->apply(Inventaire::query()->withCount('lignes'), $request)
            ->when(isset($filters['service_id']), fn ($query) => $query->where('service_id', $filters['service_id']))
            ->when(isset($filters['statut']), fn ($query) => $query->where('statut', $filters['statut']))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($inventaires);
    }

    public function store(Request $request, PatrimoineClient $patrimoine, HistoriqueWriter $historique): JsonResponse
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:180'],
            'service_id' => ['nullable', 'integer', 'min:1'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);
        $token = (string) $request->bearerToken();

        if (isset($data['service_id']) && ! $patrimoine->serviceExiste((int) $data['service_id'], $token)) {
            throw ValidationException::withMessages(['service_id' => 'Le service sélectionné n’existe pas.']);
        }

        $payload = $request->attributes->get('jwt_payload', []);
        $inventaire = DB::transaction(function () use ($data, $payload, $historique): Inventaire {
            $inventaire = Inventaire::create([
                ...$data,
                'statut' => 'brouillon',
                'createur_id' => (int) ($payload['sub'] ?? 0),
            ]);
            $historique->enregistrer('inventaire.cree', null, null, $inventaire->toArray());

            return $inventaire;
        });

        return response()->json(['data' => $inventaire], Response::HTTP_CREATED);
    }

    public function show(Request $request, Inventaire $inventaire, PatrimoineClient $patrimoine, PerimetreScope $scope): JsonResponse
    {
        $scope->authorizeService($request, $inventaire->service_id !== null ? (int) $inventaire->service_id : null);

        $filters = $request->validate([
            'biens_non_controles_page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'data' => $inventaire->load('lignes'),
            'rapport' => $this->fullReport(
                $inventaire,
                (string) $request->bearerToken(),
                $patrimoine,
                (int) ($filters['biens_non_controles_page'] ?? 1),
            ),
        ]);
    }

    public function addLine(
        Request $request,
        Inventaire $inventaire,
        PatrimoineClient $patrimoine,
        HistoriqueWriter $historique,
    ): JsonResponse {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'constat' => ['required', 'in:present,absent,deteriore'],
            'localisation_physique' => ['nullable', 'string', 'max:255'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);
        $payload = $request->attributes->get('jwt_payload', []);

        $line = DB::transaction(function () use ($inventaire, $data, $request, $payload, $patrimoine, $historique): InventaireLigne {
            $inventaire = Inventaire::query()->lockForUpdate()->findOrFail($inventaire->id);

            if ($inventaire->statut !== 'en_cours') {
                abort(Response::HTTP_CONFLICT, 'Seul un inventaire en cours peut recevoir des constats.');
            }

            if ($inventaire->lignes()->where('bien_id', $data['bien_id'])->exists()) {
                abort(Response::HTTP_CONFLICT, 'Ce bien est déjà enregistré dans cette campagne.');
            }

            $bien = $patrimoine->bien((int) $data['bien_id'], (string) $request->bearerToken());

            if ($bien === null) {
                throw ValidationException::withMessages(['bien_id' => 'Le bien sélectionné n’existe pas.']);
            }

            if ($inventaire->service_id !== null && (int) $bien['service_id'] !== (int) $inventaire->service_id) {
                throw ValidationException::withMessages(['bien_id' => 'Le bien ne fait pas partie du périmètre de cette campagne.']);
            }

            $line = $inventaire->lignes()->create([
                ...$data,
                'controleur_id' => (int) ($payload['sub'] ?? 0),
                'controle_le' => now(),
            ]);
            $historique->enregistrer('inventaire.bien_controle', (int) $line->bien_id, null, $line->toArray());

            return $line;
        });

        return response()->json(['data' => $line], Response::HTTP_CREATED);
    }

    public function updateLine(
        Request $request,
        Inventaire $inventaire,
        InventaireLigne $ligne,
        HistoriqueWriter $historique,
    ): JsonResponse {
        abort_unless((int) $ligne->inventaire_id === (int) $inventaire->id, Response::HTTP_NOT_FOUND);
        $data = $request->validate([
            'constat' => ['sometimes', 'required', 'in:present,absent,deteriore'],
            'localisation_physique' => ['sometimes', 'nullable', 'string', 'max:255'],
            'observation' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        if ($data === []) {
            throw ValidationException::withMessages(['ligne' => 'Aucune modification n’a été fournie.']);
        }

        $updated = DB::transaction(function () use ($inventaire, $ligne, $data, $historique): InventaireLigne {
            $inventaire = Inventaire::query()->lockForUpdate()->findOrFail($inventaire->id);

            if ($inventaire->statut !== 'en_cours') {
                abort(Response::HTTP_CONFLICT, 'Les constats ne peuvent plus être modifiés.');
            }

            $oldValue = $ligne->toArray();
            $ligne->update([...$data, 'controle_le' => now()]);
            $historique->enregistrer('inventaire.constat_modifie', (int) $ligne->bien_id, $oldValue, $ligne->fresh()->toArray());

            return $ligne->fresh();
        });

        return response()->json(['data' => $updated]);
    }

    public function transition(
        Request $request,
        Inventaire $inventaire,
        HistoriqueWriter $historique,
        NotificationWriter $notifications,
    ): JsonResponse {
        $data = $request->validate(['statut' => ['required', 'in:en_cours,a_valider,valide']]);
        $payload = $request->attributes->get('jwt_payload', []);

        $updated = DB::transaction(function () use ($inventaire, $data, $payload, $historique, $notifications): Inventaire {
            $inventaire = Inventaire::query()->lockForUpdate()->findOrFail($inventaire->id);
            $transitions = [
                'brouillon' => ['en_cours'],
                'en_cours' => ['a_valider'],
                'a_valider' => ['valide'],
                'valide' => [],
            ];
            $nextStatus = $data['statut'];

            if (! in_array($nextStatus, $transitions[$inventaire->statut] ?? [], true)) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'La transition demandée est invalide.');
            }

            if ($nextStatus === 'a_valider' && ! $inventaire->lignes()->exists()) {
                abort(Response::HTTP_CONFLICT, 'Un inventaire doit contenir au moins un constat avant validation.');
            }

            $oldValue = $inventaire->toArray();
            if ($nextStatus === 'en_cours') {
                $inventaire->date_debut = now()->toDateString();
            } elseif ($nextStatus === 'valide') {
                $inventaire->date_cloture = now()->toDateString();
                $inventaire->validateur_id = (int) ($payload['sub'] ?? 0);
            }
            $inventaire->statut = $nextStatus;
            $inventaire->save();
            $historique->enregistrer('inventaire.statut_modifie', null, $oldValue, $inventaire->fresh()->toArray());

            $updated = $inventaire->fresh()->loadCount('lignes');
            $actorId = (int) ($payload['sub'] ?? 0);
            if (
                $updated->createur_id !== $actorId
                && in_array($nextStatus, ['a_valider', 'valide'], true)
            ) {
                $notifications->envoyer(
                    (int) $updated->createur_id,
                    'inventaire.'.$nextStatus,
                    'Mise à jour de votre campagne d’inventaire',
                    'La campagne « '.$updated->titre.' » est maintenant « '.str_replace('_', ' ', $nextStatus).' ».',
                    ['inventaire_id' => $updated->id, 'statut' => $nextStatus],
                );
            }

            return $updated;
        });

        return response()->json(['data' => $updated, 'rapport' => $this->summary($updated)]);
    }

    private function summary(Inventaire $inventaire): array
    {
        $counts = $inventaire->lignes()
            ->selectRaw('constat, COUNT(*) AS total')
            ->groupBy('constat')
            ->pluck('total', 'constat')
            ->map(fn ($total): int => (int) $total);

        return [
            'biens_controles' => (int) $counts->sum(),
            'presents' => (int) ($counts['present'] ?? 0),
            'absents' => (int) ($counts['absent'] ?? 0),
            'deteriores' => (int) ($counts['deteriore'] ?? 0),
        ];
    }

    private function fullReport(
        Inventaire $inventaire,
        string $token,
        PatrimoineClient $patrimoine,
        int $page,
    ): array {
        $theoreticalAssets = array_values(array_filter(
            $patrimoine->biens($inventaire->service_id, $token),
            static fn (array $asset): bool => ($asset['actif'] ?? true) === true,
        ));
        $checkedIds = $inventaire->lignes()->pluck('bien_id')->map(fn ($id): int => (int) $id)->all();
        $unchecked = array_values(array_filter(
            $theoreticalAssets,
            static fn (array $asset): bool => ! in_array((int) $asset['id'], $checkedIds, true),
        ));
        $pageSize = 50;
        $page = max(1, $page);
        $result = $this->summary($inventaire);
        $result['biens_theoriques'] = count($theoreticalAssets);
        $result['biens_non_controles'] = array_map(
            static fn (array $asset): array => [
                'id' => (int) $asset['id'],
                'reference' => $asset['reference'] ?? null,
                'designation' => $asset['designation'] ?? null,
                'service_id' => isset($asset['service_id']) ? (int) $asset['service_id'] : null,
            ],
            array_slice($unchecked, ($page - 1) * $pageSize, $pageSize),
        );
        $result['non_controles'] = count($unchecked);
        $result['ecarts'] = (int) ($result['absents'] + $result['deteriores'] + $result['non_controles']);
        $result['pagination_non_controles'] = [
            'current_page' => $page,
            'per_page' => $pageSize,
            'total' => count($unchecked),
            'last_page' => max(1, (int) ceil(count($unchecked) / $pageSize)),
        ];

        return $result;
    }
}
