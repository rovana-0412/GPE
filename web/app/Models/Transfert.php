<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Le transfert concerne un bien.
     */
    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }

    /**
     * Service qui cède le bien.
     */
    public function serviceOrigine(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_origine_id');
    }

    /**
     * Service qui reçoit le bien.
     */
    public function serviceDestination(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_destination_id');
    }

    /**
     * Utilisateur qui demande le transfert.
     */
    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    /**
     * Utilisateur qui valide ou refuse le transfert.
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }
}