<?php

namespace App\Services;

use App\Models\JournalAction;
use Illuminate\Http\Request;

class AuditWriter
{
    public function __construct(private readonly Request $request) {}

    public function enregistrer(
        string $ressource,
        ?int $ressourceId,
        string $action,
        ?array $ancienneValeur = null,
        ?array $nouvelleValeur = null,
        ?int $bienId = null,
    ): JournalAction {
        $payload = $this->request->attributes->get('jwt_payload', []);

        return JournalAction::create([
            'utilisateur_id' => is_numeric($payload['sub'] ?? null) ? (int) $payload['sub'] : null,
            'bien_id' => $bienId,
            'ressource' => $ressource,
            'ressource_id' => $ressourceId,
            'action' => $action,
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'adresse_ip' => $this->request->ip(),
        ]);
    }
}
