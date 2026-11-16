<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acquisition extends Model
{
    protected $fillable = [
        'reference',
        'designation',
        'description',
        'categorie_id',
        'service_id',
        'numero_serie',
        'localisation',
        'fournisseur',
        'cout',
        'date_acquisition',
        'mode_acquisition',
        'reference_facture',
        'reference_bon_commande',
        'statut',
        'bien_id',
        'demandeur_id',
        'validateur_id',
        'receptionnee_le',
        'validee_le',
        'motif_rejet',
    ];

    protected function casts(): array
    {
        return [
            'cout' => 'decimal:2',
            'date_acquisition' => 'date',
            'receptionnee_le' => 'datetime',
            'validee_le' => 'datetime',
        ];
    }

    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
