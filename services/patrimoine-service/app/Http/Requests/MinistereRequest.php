<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinistereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'nom' => [$required, 'string', 'max:150', Rule::unique('ministeres', 'nom')->ignore($this->route('ministere'))],
            'sigle' => ['nullable', 'string', 'max:30', Rule::unique('ministeres', 'sigle')->ignore($this->route('ministere'))],
            'description' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}
