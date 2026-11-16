<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentJustificatifPerimetre extends Model
{
    protected $fillable = [
        'service_id',
        'ministere_id',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentJustificatif::class, 'document_id');
    }
}
