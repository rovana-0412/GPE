<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bien extends Model
{
    protected $fillable = [
        'reference',
        'designation',
        'description',
        'categorie_id',
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

    /**
     * Un bien appartient à une catégorie.
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    /**
     * Un bien peut avoir plusieurs affectations au cours du temps.
     */
    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    /**
     * Un bien peut faire l'objet de plusieurs transferts.
     */
    public function transferts(): HasMany
    {
        return $this->hasMany(Transfert::class);
    }

    /**
     * Un bien peut avoir plusieurs opérations de maintenance.
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    /**
     * Un bien peut avoir plusieurs entrées dans l'historique.
     */
    public function historiqueOperations(): HasMany
    {
        return $this->hasMany(HistoriqueOperation::class);
    }
}