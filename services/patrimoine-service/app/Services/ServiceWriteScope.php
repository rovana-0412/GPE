<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceWriteScope
{
    public function serviceIds(Request $request): ?array
    {
        $payload = $request->attributes->get('jwt_payload', []);
        $role = $payload['role'] ?? '';

        if (in_array($role, ['administrateur-general', 'gestionnaire-patrimoine'], true)) {
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
            $ministereId = Service::query()->whereKey($assignedServiceId)->value('ministere_id');

            return $ministereId === null
                ? []
                : Service::query()->where('ministere_id', $ministereId)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        return [];
    }

    public function authorize(Request $request, ?int $serviceId): void
    {
        $allowed = $this->serviceIds($request);
        if ($allowed !== null && ($serviceId === null || ! in_array($serviceId, $allowed, true))) {
            abort(Response::HTTP_FORBIDDEN, 'Cette opération est hors de votre périmètre de service.');
        }
    }

    public function apply(Builder $query, Request $request, string $column = 'service_id'): Builder
    {
        $allowed = $this->serviceIds($request);

        return $allowed === null ? $query : $query->whereIn($column, $allowed);
    }
}
