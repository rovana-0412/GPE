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

    public function ministere(): BelongsTo
    {
        return $this->belongsTo(Ministere::class);
    }

    public function biens(): HasMany
    {
        return $this->hasMany(Bien::class);
    }
}
