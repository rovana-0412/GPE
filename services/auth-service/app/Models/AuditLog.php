<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'acteur_id',
        'sujet_id',
        'action',
        'ancienne_valeur',
        'nouvelle_valeur',
        'adresse_ip',
    ];

    protected function casts(): array
    {
        return [
            'ancienne_valeur' => 'array',
            'nouvelle_valeur' => 'array',
        ];
    }
}
