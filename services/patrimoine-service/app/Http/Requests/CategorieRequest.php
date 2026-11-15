<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'nom' => [$required, 'string', 'max:100', Rule::unique('categories', 'nom')->ignore($this->route('categorie'))],
            'type' => [$required, 'string', Rule::in(['immobilier', 'mobilier'])],
            'description' => ['nullable', 'string'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
