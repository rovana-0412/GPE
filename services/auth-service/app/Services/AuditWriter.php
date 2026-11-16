<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditWriter
{
    public function __construct(private readonly Request $request) {}

    public function utilisateur(string $action, ?User $ancien, ?User $nouveau): AuditLog
    {
        $payload = $this->request->attributes->get('jwt_payload', []);
        $subject = $nouveau ?? $ancien;

        return AuditLog::create([
            'acteur_id' => is_numeric($payload['sub'] ?? null) ? (int) $payload['sub'] : null,
            'sujet_id' => $subject?->id,
            'action' => $action,
            'ancienne_valeur' => $ancien ? $this->instantane($ancien) : null,
            'nouvelle_valeur' => $nouveau ? $this->instantane($nouveau) : null,
            'adresse_ip' => $this->request->ip(),
        ]);
    }

    public function parametres(array $ancienneValeur, array $nouvelleValeur): AuditLog
    {
        $payload = $this->request->attributes->get('jwt_payload', []);

        return AuditLog::create([
            'acteur_id' => is_numeric($payload['sub'] ?? null) ? (int) $payload['sub'] : null,
            'action' => 'parametres.modifies',
            'ancienne_valeur' => $ancienneValeur,
            'nouvelle_valeur' => $nouvelleValeur,
            'adresse_ip' => $this->request->ip(),
        ]);
    }

    public function authentification(string $action, ?User $user): AuditLog
    {
        $connexionReussie = $action === 'authentification.reussie';

        return AuditLog::create([
            'acteur_id' => $connexionReussie ? $user?->id : null,
            'sujet_id' => $user?->id,
            'action' => $action,
            'ancienne_valeur' => null,
            'nouvelle_valeur' => null,
            'adresse_ip' => $this->request->ip(),
        ]);
    }

    private function instantane(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'service_id' => $user->service_id,
            'actif' => $user->actif,
        ];
    }
}
