<?php

namespace App\Services;

use App\Models\HistoriqueOperation;
use Illuminate\Http\Request;

class HistoriqueWriter
{
    public function __construct(private readonly Request $request) {}

    public function enregistrer(
        string $action,
        ?int $bienId,
        ?array $ancienneValeur,
        ?array $nouvelleValeur,
        ?string $description = null,
    ): HistoriqueOperation {
        $payload = $this->request->attributes->get('jwt_payload', []);
        $userId = is_numeric($payload['sub'] ?? null) ? (int) $payload['sub'] : null;

        return HistoriqueOperation::create([
            'utilisateur_id' => $userId,
            'bien_id' => $bienId,
            'action' => $action,
            'description' => $description,
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'adresse_ip' => $this->request->ip(),
        ]);
    }
}
