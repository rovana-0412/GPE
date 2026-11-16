<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\DocumentJustificatif;
use App\Models\Maintenance;
use App\Models\Transfert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DocumentAccessService
{
    public function userScope(array $payload, string $token, PatrimoineClient $patrimoine): array
    {
        $role = $payload['role'] ?? '';
        $userId = $payload['sub'] ?? null;
        abort_unless(is_numeric($userId) && (int) $userId > 0, Response::HTTP_UNAUTHORIZED);

        if (in_array($role, ['administrateur-general', 'gestionnaire-patrimoine', 'auditeur'], true)) {
            return ['user_id' => (int) $userId, 'global' => true, 'service_ids' => [], 'ministere_ids' => []];
        }

        $serviceId = $payload['service_id'] ?? null;
        if (! is_numeric($serviceId) || (int) $serviceId < 1) {
            return ['user_id' => (int) $userId, 'global' => false, 'service_ids' => [], 'ministere_ids' => []];
        }

        if ($role === 'agent-gestionnaire') {
            return [
                'user_id' => (int) $userId,
                'global' => false,
                'service_ids' => [(int) $serviceId],
                'ministere_ids' => [],
            ];
        }

        if ($role === 'responsable-ministeriel') {
            $service = $patrimoine->service((int) $serviceId, $token);
            if ($service === null) {
                return ['user_id' => (int) $userId, 'global' => false, 'service_ids' => [], 'ministere_ids' => []];
            }

            return [
                'user_id' => (int) $userId,
                'global' => false,
                'service_ids' => [],
                'ministere_ids' => [(int) $service['ministere_id']],
            ];
        }

        return ['user_id' => (int) $userId, 'global' => false, 'service_ids' => [], 'ministere_ids' => []];
    }

    public function applyScope(Builder $query, array $scope): Builder
    {
        if ($scope['global']) {
            return $query;
        }

        return $query->where(function (Builder $documents) use ($scope): void {
            $documents->where('utilisateur_id', $scope['user_id'])
                ->orWhereHas('perimetres', function (Builder $perimetres) use ($scope): void {
                    if ($scope['service_ids'] !== []) {
                        $perimetres->whereIn('service_id', $scope['service_ids']);
                    } elseif ($scope['ministere_ids'] !== []) {
                        $perimetres->whereIn('ministere_id', $scope['ministere_ids']);
                    } else {
                        $perimetres->whereRaw('1 = 0');
                    }
                });
        });
    }

    public function canView(DocumentJustificatif $document, array $scope): bool
    {
        if ($scope['global'] || (int) $document->utilisateur_id === $scope['user_id']) {
            return true;
        }

        return $document->perimetres()
            ->where(function (Builder $query) use ($scope): void {
                if ($scope['service_ids'] !== []) {
                    $query->whereIn('service_id', $scope['service_ids']);
                } elseif ($scope['ministere_ids'] !== []) {
                    $query->whereIn('ministere_id', $scope['ministere_ids']);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->exists();
    }

    public function resolveTarget(
        string $type,
        int $id,
        string $token,
        PatrimoineClient $patrimoine,
    ): array {
        $bienId = null;
        $serviceIds = [];

        switch ($type) {
            case 'bien':
                $bien = $patrimoine->bien($id, $token);
                if ($bien === null) {
                    $this->missingTarget();
                }
                $bienId = (int) $bien['id'];
                $serviceIds[] = (int) $bien['service_id'];
                break;
            case 'acquisition':
                $acquisition = $patrimoine->acquisition($id, $token);
                if ($acquisition === null) {
                    $this->missingTarget();
                }
                $bienId = isset($acquisition['bien_id']) ? (int) $acquisition['bien_id'] : null;
                if (isset($acquisition['service_id'])) {
                    $serviceIds[] = (int) $acquisition['service_id'];
                } elseif ($bienId !== null) {
                    $bien = $patrimoine->bien($bienId, $token);
                    if ($bien !== null) {
                        $serviceIds[] = (int) $bien['service_id'];
                    }
                }
                break;
            case 'affectation':
                $operation = Affectation::query()->find($id);
                if ($operation === null) {
                    $this->missingTarget();
                }
                $bienId = $operation->bien_id === null ? null : (int) $operation->bien_id;
                if ($operation->service_id !== null) {
                    $serviceIds[] = (int) $operation->service_id;
                }
                break;
            case 'transfert':
                $operation = Transfert::query()->find($id);
                if ($operation === null) {
                    $this->missingTarget();
                }
                $bienId = $operation->bien_id === null ? null : (int) $operation->bien_id;
                $serviceIds = array_filter([
                    $operation->service_origine_id,
                    $operation->service_destination_id,
                ], fn ($serviceId): bool => $serviceId !== null);
                break;
            case 'maintenance':
                $operation = Maintenance::query()->find($id);
                if ($operation === null) {
                    $this->missingTarget();
                }
                $bienId = $operation->bien_id === null ? null : (int) $operation->bien_id;
                if ($bienId !== null) {
                    $bien = $patrimoine->bien($bienId, $token);
                    if ($bien !== null) {
                        $serviceIds[] = (int) $bien['service_id'];
                    }
                }
                break;
            default:
                $this->missingTarget();
        }

        $perimetres = [];
        foreach (array_unique(array_map('intval', $serviceIds)) as $serviceId) {
            $service = $patrimoine->service($serviceId, $token);
            if ($service === null) {
                $this->missingTarget();
            }
            $perimetres[] = [
                'service_id' => $serviceId,
                'ministere_id' => (int) $service['ministere_id'],
            ];
        }

        return ['bien_id' => $bienId, 'perimetres' => $perimetres];
    }

    private function missingTarget(): never
    {
        throw ValidationException::withMessages([
            'cible_id' => 'Le bien ou l’opération sélectionné n’existe pas ou ne possède pas de périmètre valide.',
        ]);
    }
}
