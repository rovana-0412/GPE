<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventaire extends Model
{
    protected $fillable = [
        'titre',
        'service_id',
        'statut',
        'date_debut',
        'date_cloture',
        'createur_id',
        'validateur_id',
        'observation',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_cloture' => 'date',
        ];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(InventaireLigne::class);
    }
}
