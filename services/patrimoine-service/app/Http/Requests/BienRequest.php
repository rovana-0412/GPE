<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'reference' => [$required, 'string', 'max:50', Rule::unique('biens', 'reference')->ignore($this->route('bien'))],
            'designation' => [$required, 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'categorie_id' => [$required, 'integer', 'exists:categories,id'],
            'service_id' => [$required, 'integer', 'exists:services,id'],
            'numero_serie' => ['nullable', 'string', 'max:100', Rule::unique('biens', 'numero_serie')->ignore($this->route('bien'))],
            'date_acquisition' => ['nullable', 'date'],
            'valeur_acquisition' => ['nullable', 'numeric', 'min:0'],
            'etat' => ['sometimes', 'string', 'max:30'],
            'statut' => ['sometimes', 'string', 'max:30'],
            'localisation' => ['nullable', 'string', 'max:255'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reference' => 'référence',
            'designation' => 'désignation',
            'categorie_id' => 'catégorie',
            'service_id' => 'service',
            'numero_serie' => 'numéro de série',
            'date_acquisition' => 'date d’acquisition',
            'valeur_acquisition' => 'valeur d’acquisition',
            'etat' => 'état',
            'statut' => 'statut',
        ];
    }
}
