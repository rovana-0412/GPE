<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affectation extends Model
{
    protected $fillable = [
        'bien_id',
        'service_id',
        'utilisateur_id',
        'date_debut',
        'date_fin',
        'motif',
        'observation',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }
}
