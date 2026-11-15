<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'bien_id',
        'utilisateur_id',
        'type',
        'description',
        'date_debut',
        'date_fin',
        'cout',
        'prestataire',
        'statut',
        'observation',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'cout' => 'decimal:2',
        ];
    }
}
