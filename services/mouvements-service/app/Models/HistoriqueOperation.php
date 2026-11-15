<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
