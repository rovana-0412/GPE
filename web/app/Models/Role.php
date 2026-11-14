<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'nom',
        'description',
    ];

    /**
     * Un rôle peut être attribué à plusieurs utilisateurs.
     */
    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }
}