<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cloisonne les lectures des ressources de mouvements selon le périmètre
 * du rôle porté par le JWT (service_id). null = accès global.
 */
class PerimetreScope
{
    /** @var array<int, array<int, int>> */
    private array $ministereCache = [];

    public function __construct(private readonly PatrimoineClient $patrimoine) {}

    public function serviceIds(Request $request): ?array
    {
        $payload = $request->attributes->get('jwt_payload', []);
        $role = $payload['role'] ?? '';

        if (in_array($role, ['administrateur-general', 'gestionnaire-patrimoine', 'auditeur'], true)) {
            return null;
        }

        $assignedServiceId = filter_var($payload['service_id'] ?? null, FILTER_VALIDATE_INT);
        if ($assignedServiceId === false || $assignedServiceId < 1) {
            return [];
        }

        if ($role === 'agent-gestionnaire') {
            return [$assignedServiceId];
        }

        if ($role === 'responsable-ministeriel') {
            $token = (string) $request->bearerToken();
            $service = $this->patrimoine->service($assignedServiceId, $token);

            if ($service === null) {
                return [];
            }

            return $this->servicesOfMinistere((int) $service['ministere_id'], $token);
        }

        return [];
    }

    /** @return array<int, int>|null null = aucune restriction */
    public function bienIds(Request $request): ?array
    {
        $serviceIds = $this->serviceIds($request);
        if ($serviceIds === null) {
            return null;
        }

        $token = (string) $request->bearerToken();
        $bienIds = [];
        foreach ($serviceIds as $serviceId) {
            foreach ($this->patrimoine->biens($serviceId, $token) as $bien) {
                $bienIds[] = (int) $bien['id'];
            }
        }

        return array_values(array_unique($bienIds));
    }

    public function apply(Builder $query, Request $request, string $column = 'service_id'): Builder
    {
        $allowed = $this->serviceIds($request);

        return $allowed === null ? $query : $query->whereIn($column, $allowed);
    }

    /** Filtre « origine OU destination » pour les transferts. */
    public function applyTransfert(Builder $query, Request $request): Builder
    {
        $allowed = $this->serviceIds($request);

        return $allowed === null
            ? $query
            : $query->where(fn (Builder $q) => $q
                ->whereIn('service_origine_id', $allowed)
                ->orWhereIn('service_destination_id', $allowed));
    }

    public function applyBien(Builder $query, Request $request, string $column = 'bien_id'): Builder
    {
        $bienIds = $this->bienIds($request);

        return $bienIds === null ? $query : $query->whereIn($column, $bienIds);
    }

    public function authorizeService(Request $request, ?int $serviceId): void
    {
        $allowed = $this->serviceIds($request);
        if ($allowed !== null && ($serviceId === null || ! in_array($serviceId, $allowed, true))) {
            abort(Response::HTTP_FORBIDDEN, 'Cette ressource est hors de votre périmètre.');
        }
    }

    public function authorizeTransfert(Request $request, int $origineId, int $destinationId): void
    {
        $allowed = $this->serviceIds($request);
        if ($allowed !== null
            && ! in_array($origineId, $allowed, true)
            && ! in_array($destinationId, $allowed, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Ce transfert est hors de votre périmètre.');
        }
    }

    public function authorizeBien(Request $request, ?int $bienId): void
    {
        $bienIds = $this->bienIds($request);
        if ($bienIds !== null && ($bienId === null || ! in_array($bienId, $bienIds, true))) {
            abort(Response::HTTP_FORBIDDEN, 'Ce bien est hors de votre périmètre.');
        }
    }

    /** @return array<int, int> */
    private function servicesOfMinistere(int $ministereId, string $token): array
    {
        if (isset($this->ministereCache[$ministereId])) {
            return $this->ministereCache[$ministereId];
        }

        $ids = [];
        foreach ($this->patrimoine->services($token) as $service) {
            if ((int) ($service['ministere_id'] ?? 0) === $ministereId) {
                $ids[] = (int) $service['id'];
            }
        }

        return $this->ministereCache[$ministereId] = $ids;
    }
}
