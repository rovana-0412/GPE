<?php

namespace App\Services;

use App\Models\NotificationMetier;

class NotificationWriter
{
    public function envoyer(
        int $utilisateurId,
        string $type,
        string $titre,
        string $message,
        array $donnees = [],
    ): NotificationMetier {
        return NotificationMetier::create([
            'utilisateur_id' => $utilisateurId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'donnees' => $donnees,
        ]);
    }
}
