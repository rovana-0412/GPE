<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentJustificatif extends Model
{
    protected $table = 'documents_justificatifs';

    protected $fillable = [
        'cible_type',
        'cible_id',
        'type_document',
        'titre',
        'reference',
        'description',
        'date_document',
        'nom_original',
        'chemin',
        'mime_type',
        'taille_octets',
        'utilisateur_id',
    ];

    protected $hidden = [
        'chemin',
    ];

    protected function casts(): array
    {
        return [
            'date_document' => 'date:Y-m-d',
            'taille_octets' => 'integer',
        ];
    }

    public function perimetres(): HasMany
    {
        return $this->hasMany(DocumentJustificatifPerimetre::class, 'document_id');
    }
}
