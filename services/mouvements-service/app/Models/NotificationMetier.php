<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationMetier extends Model
{
    protected $table = 'notifications_metier';

    protected $fillable = [
        'utilisateur_id',
        'type',
        'titre',
        'message',
        'donnees',
        'lue_le',
    ];

    protected function casts(): array
    {
        return [
            'donnees' => 'array',
            'lue_le' => 'datetime',
        ];
    }
}
