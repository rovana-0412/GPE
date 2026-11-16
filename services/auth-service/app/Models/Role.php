<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
    ];

    public function slug(): string
    {
        return match (Str::slug($this->nom)) {
            'gestionnaire-du-patrimoine' => 'gestionnaire-patrimoine',
            default => Str::slug($this->nom),
        };
    }
}
