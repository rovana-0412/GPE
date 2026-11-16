<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventaireLigne extends Model
{
    protected $fillable = [
        'inventaire_id',
        'bien_id',
        'constat',
        'localisation_physique',
        'observation',
        'controleur_id',
        'controle_le',
    ];

    protected function casts(): array
    {
        return ['controle_le' => 'datetime'];
    }

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }
}
