<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SortiePatrimoine extends Model
{
    protected $table = 'sorties_patrimoine';

    protected $fillable = [
        'bien_id',
        'type_sortie',
        'motif',
        'statut',
        'demandeur_id',
        'validateur_id',
        'date_demande',
        'date_execution',
        'motif_rejet',
        'observation',
    ];

    protected function casts(): array
    {
        return [
            'date_demande' => 'date',
            'date_execution' => 'date',
        ];
    }

    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }
}
