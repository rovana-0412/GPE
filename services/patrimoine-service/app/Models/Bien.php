<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bien extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'designation',
        'description',
        'categorie_id',
        'service_id',
        'numero_serie',
        'date_acquisition',
        'valeur_acquisition',
        'etat',
        'statut',
        'localisation',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'date_acquisition' => 'date',
            'valeur_acquisition' => 'decimal:2',
            'actif' => 'boolean',
        ];
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
