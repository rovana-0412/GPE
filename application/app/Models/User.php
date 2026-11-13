<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'service_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Le rôle attribué à l'utilisateur.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Le service auquel appartient l'utilisateur.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Les affectations enregistrées par l'utilisateur.
     */
    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'utilisateur_id');
    }

    /**
     * Les maintenances enregistrées par l'utilisateur.
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'utilisateur_id');
    }

    /**
     * Les transferts demandés par l'utilisateur.
     */
    public function transfertsDemandes(): HasMany
    {
        return $this->hasMany(Transfert::class, 'demandeur_id');
    }

    /**
     * Les transferts validés par l'utilisateur.
     */
    public function transfertsValides(): HasMany
    {
        return $this->hasMany(Transfert::class, 'validateur_id');
    }

    /**
     * Les opérations effectuées par l'utilisateur.
     */
    public function historiqueOperations(): HasMany
    {
        return $this->hasMany(HistoriqueOperation::class, 'utilisateur_id');
    }
}