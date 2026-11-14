<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * La maintenance concerne un bien.
     */
    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }

    /**
     * Utilisateur ayant enregistré la maintenance.
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}