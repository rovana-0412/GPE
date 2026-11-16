<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalAction extends Model
{
    protected $table = 'journal_actions';

    protected $fillable = [
        'utilisateur_id',
        'bien_id',
        'ressource',
        'ressource_id',
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
