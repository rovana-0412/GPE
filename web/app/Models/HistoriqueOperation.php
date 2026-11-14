<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueOperation extends Model
{
    protected $table = 'historique_operations';

    protected $fillable = [
        'utilisateur_id',
        'bien_id',
        'action',
        'description',
        'ancienne_valeur',
        'nouvelle_valeur',
        'adresse_ip',
    ];

    protected function casts(): array
    {
        return [
            'ancienne_valeur' => 'array',
            'nouvelle_valeur' => 'array',
        ];
    }

    /**
     * Utilisateur ayant effectué l'opération.
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Bien concerné par l'opération.
     */
    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }
}