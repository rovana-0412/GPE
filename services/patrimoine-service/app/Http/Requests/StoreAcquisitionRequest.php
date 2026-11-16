<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcquisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => [
                'required',
                'string',
                'max:50',
                Rule::unique('acquisitions', 'reference'),
                Rule::unique('biens', 'reference'),
            ],
            'designation' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:10000'],
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'numero_serie' => ['nullable', 'string', 'max:100', Rule::unique('biens', 'numero_serie')],
            'localisation' => ['nullable', 'string', 'max:255'],
            'fournisseur' => ['required', 'string', 'max:180'],
            'cout' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'date_acquisition' => ['required', 'date'],
            'mode_acquisition' => ['required', 'in:achat,don,dotation,transfert,autre'],
            'reference_facture' => ['nullable', 'string', 'max:100'],
            'reference_bon_commande' => ['nullable', 'string', 'max:100'],
        ];
    }
}
