<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ministere extends Model
{
    protected $fillable = [
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

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
