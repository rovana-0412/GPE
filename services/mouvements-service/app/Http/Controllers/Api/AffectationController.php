<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Services\HistoriqueWriter;
use App\Services\PatrimoineClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AffectationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'bien_id' => ['nullable', 'integer', 'min:1'],
            'service_id' => ['nullable', 'integer', 'min:1'],
            'statut' => ['nullable', 'in:active,terminee,annulee'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $affectations = Affectation::query()
            ->when(isset($filters['bien_id']), fn ($query) => $query->where('bien_id', $filters['bien_id']))
            ->when(isset($filters['service_id']), fn ($query) => $query->where('service_id', $filters['service_id']))
            ->when(isset($filters['statut']), fn ($query) => $query->where('statut', $filters['statut']))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($affectations);
    }

    public function store(Request $request, PatrimoineClient $patrimoine, HistoriqueWriter $historique): JsonResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'service_id' => ['required', 'integer', 'min:1'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'motif' => ['nullable', 'string', 'max:500'],
            'observation' => ['nullable', 'string', 'max:5000'],
            'statut' => ['sometimes', 'in:active,terminee,annulee'],
        ]);

        $token = (string) $request->bearerToken();
        $bien = $patrimoine->bien($data['bien_id'], $token);

        if ($bien === null) {
            throw ValidationException::withMessages(['bien_id' => 'Le bien demandé n’existe pas.']);
        }

        if (! $patrimoine->serviceExiste($data['service_id'], $token)) {
            throw ValidationException::withMessages(['service_id' => 'Le service demandé n’existe pas.']);
        }

        if (($data['statut'] ?? 'active') === 'active' && Affectation::where('bien_id', $data['bien_id'])->where('statut', 'active')->exists()) {
            return response()->json(['message' => 'Ce bien possède déjà une affectation active.'], Response::HTTP_CONFLICT);
        }

        $payload = $request->attributes->get('jwt_payload', []);
        $affectation = DB::transaction(function () use ($data, $payload, $historique): Affectation {
            $affectation = Affectation::create([
                ...$data,
                'utilisateur_id' => (int) $payload['sub'],
                'statut' => $data['statut'] ?? 'active',
            ]);
            $historique->enregistrer('affectation.creee', $affectation->bien_id, null, $affectation->toArray());

            return $affectation;
        });

        return response()->json(['data' => $affectation], Response::HTTP_CREATED);
    }

    public function show(Affectation $affectation): JsonResponse
    {
        return response()->json(['data' => $affectation]);
    }

    public function update(
        Request $request,
        Affectation $affectation,
        PatrimoineClient $patrimoine,
        HistoriqueWriter $historique,
    ): JsonResponse {
        $data = $request->validate([
            'bien_id' => ['sometimes', 'integer', 'min:1'],
            'service_id' => ['sometimes', 'integer', 'min:1'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'nullable', 'date'],
            'motif' => ['sometimes', 'nullable', 'string', 'max:500'],
            'observation' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'statut' => ['sometimes', 'in:active,terminee,annulee'],
        ]);

        if ($data === []) {
            throw ValidationException::withMessages(['affectation' => 'Aucune modification n’a été fournie.']);
        }

        $bienId = (int) ($data['bien_id'] ?? $affectation->bien_id);
        $serviceId = (int) ($data['service_id'] ?? $affectation->service_id);
        $token = (string) $request->bearerToken();

        if ($patrimoine->bien($bienId, $token) === null) {
            throw ValidationException::withMessages(['bien_id' => 'Le bien demandé n’existe pas.']);
        }

        if (! $patrimoine->serviceExiste($serviceId, $token)) {
            throw ValidationException::withMessages(['service_id' => 'Le service demandé n’existe pas.']);
        }

        $hasActiveAssignment = Affectation::where('bien_id', $bienId)
            ->where('statut', 'active')
            ->where('id', '!=', $affectation->id)
            ->exists();

        if (($data['statut'] ?? $affectation->statut) === 'active' && $hasActiveAssignment) {
            return response()->json(['message' => 'Ce bien possède déjà une affectation active.'], Response::HTTP_CONFLICT);
        }

        DB::transaction(function () use ($affectation, $data, $historique): void {
            $ancienneValeur = $affectation->toArray();
            $affectation->update($data);
            $historique->enregistrer(
                'affectation.modifiee',
                $affectation->bien_id,
                $ancienneValeur,
                $affectation->fresh()->toArray(),
            );
        });
        $affectation->refresh();

        return response()->json(['data' => $affectation]);
    }
}
