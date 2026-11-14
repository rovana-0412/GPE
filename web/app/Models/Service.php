<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'ministere_id',
        'nom',
        'sigle',
        'description',
        'adresse',
        'telephone',
        'email',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    /**
     * Un service appartient à un ministère.
     */
    public function ministere(): BelongsTo
    {
        return $this->belongsTo(Ministere::class);
    }

    /**
     * Un service peut avoir plusieurs utilisateurs.
     */
    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Un service peut recevoir plusieurs biens en affectation.
     */
    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    /**
     * Un service peut être à l'origine de plusieurs transferts.
     */
    public function transfertsOrigine(): HasMany
    {
        return $this->hasMany(Transfert::class, 'service_origine_id');
    }

    /**
     * Un service peut recevoir plusieurs transferts.
     */
    public function transfertsDestination(): HasMany
    {
        return $this->hasMany(Transfert::class, 'service_destination_id');
    }
}