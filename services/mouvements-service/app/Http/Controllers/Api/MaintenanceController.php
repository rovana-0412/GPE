<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Services\HistoriqueWriter;
use App\Services\NotificationWriter;
use App\Services\PatrimoineClient;
use App\Services\PerimetreScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceController extends Controller
{
    public function index(Request $request, PerimetreScope $scope): JsonResponse
    {
        $filters = $request->validate([
            'bien_id' => ['nullable', 'integer', 'min:1'],
            'bien_ids' => ['nullable', 'array', 'max:100'],
            'bien_ids.*' => ['integer', 'min:1'],
            'statut' => ['nullable', 'in:signalee,planifiee,en_cours,terminee,annulee'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $maintenances = $scope
            ->applyBien(Maintenance::query(), $request)
            ->when(isset($filters['bien_id']), fn ($query) => $query->where('bien_id', $filters['bien_id']))
            ->when(isset($filters['bien_ids']), fn ($query) => $query->whereIn('bien_id', $filters['bien_ids']))
            ->when(isset($filters['statut']), fn ($query) => $query->where('statut', $filters['statut']))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($maintenances);
    }

    public function store(Request $request, PatrimoineClient $patrimoine, HistoriqueWriter $historique): JsonResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:5000'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'prestataire' => ['nullable', 'string', 'max:255'],
            'statut' => ['sometimes', 'in:signalee,planifiee,en_cours,terminee,annulee'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);

        $token = (string) $request->bearerToken();

        if ($patrimoine->bien($data['bien_id'], $token) === null) {
            throw ValidationException::withMessages(['bien_id' => 'Le bien demandé n’existe pas.']);
        }

        $payload = $request->attributes->get('jwt_payload', []);
        $maintenance = DB::transaction(function () use ($data, $payload, $historique): Maintenance {
            $maintenance = Maintenance::create([
                ...$data,
                'utilisateur_id' => (int) $payload['sub'],
                'statut' => $data['statut'] ?? 'signalee',
            ]);
            $historique->enregistrer('maintenance.creee', $maintenance->bien_id, null, $maintenance->toArray());

            return $maintenance;
        });

        return response()->json(['data' => $maintenance], Response::HTTP_CREATED);
    }

    public function show(Request $request, Maintenance $maintenance, PerimetreScope $scope): JsonResponse
    {
        $scope->authorizeBien($request, $maintenance->bien_id !== null ? (int) $maintenance->bien_id : null);

        return response()->json(['data' => $maintenance]);
    }

    public function update(
        Request $request,
        Maintenance $maintenance,
        PatrimoineClient $patrimoine,
        HistoriqueWriter $historique,
        NotificationWriter $notifications,
    ): JsonResponse {
        $data = $request->validate([
            'bien_id' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', 'string', 'max:50'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'nullable', 'date'],
            'cout' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'prestataire' => ['sometimes', 'nullable', 'string', 'max:255'],
            'statut' => ['sometimes', 'in:signalee,planifiee,en_cours,terminee,annulee'],
            'observation' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        if ($data === []) {
            throw ValidationException::withMessages(['maintenance' => 'Aucune modification n’a été fournie.']);
        }

        $bienId = (int) ($data['bien_id'] ?? $maintenance->bien_id);

        if ($patrimoine->bien($bienId, (string) $request->bearerToken()) === null) {
            throw ValidationException::withMessages(['bien_id' => 'Le bien demandé n’existe pas.']);
        }

        DB::transaction(function () use ($request, $maintenance, $data, $historique, $notifications): void {
            $ancienneValeur = $maintenance->toArray();
            $maintenance->update($data);
            $historique->enregistrer(
                'maintenance.modifiee',
                $maintenance->bien_id,
                $ancienneValeur,
                $maintenance->fresh()->toArray(),
            );

            $actorId = (int) $request->attributes->get('jwt_payload')['sub'];
            $nouveauStatut = $maintenance->statut;
            if (isset($data['statut'])
                && $ancienneValeur['statut'] !== $nouveauStatut
                && (int) $maintenance->utilisateur_id !== $actorId) {
                $notifications->envoyer(
                    (int) $maintenance->utilisateur_id,
                    'maintenance.'.$nouveauStatut,
                    'Mise à jour de votre maintenance',
                    'La maintenance #'.$maintenance->id.' est maintenant « '.$nouveauStatut.' ».',
                    ['maintenance_id' => $maintenance->id, 'bien_id' => $maintenance->bien_id, 'statut' => $nouveauStatut],
                );
            }
        });
        $maintenance->refresh();

        return response()->json(['data' => $maintenance]);
    }
}
