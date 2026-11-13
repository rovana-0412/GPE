<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    protected $fillable = [
        'nom',
        'type',
        'description',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    /**
     * Une catégorie peut regrouper plusieurs biens.
     */
    public function biens(): HasMany
    {
        return $this->hasMany(Bien::class);
    }
}