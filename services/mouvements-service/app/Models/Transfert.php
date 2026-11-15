<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfert extends Model
{
    protected $fillable = [
        'bien_id',
        'service_origine_id',
        'service_destination_id',
        'demandeur_id',
        'validateur_id',
        'date_demande',
        'date_validation',
        'date_effective',
        'motif',
        'observation',
        'motif_refus',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_demande' => 'date',
            'date_validation' => 'date',
            'date_effective' => 'date',
        ];
    }
}
